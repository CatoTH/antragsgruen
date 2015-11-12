<?php

namespace app\models\forms;

use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentSupporter;
use app\models\db\Consultation;
use app\models\db\IMotion;
use app\models\db\ISupporter;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use yii\base\Model;
use yii\helpers\Html;

class AdminMotionFilterForm extends Model
{
    const SORT_TYPE         = 0;
    const SORT_STATUS       = 1;
    const SORT_TITLE        = 2;
    const SORT_TITLE_PREFIX = 3;
    const SORT_INITIATOR    = 4;
    const SORT_TAG          = 5;

    /** @var int */
    public $status = null;
    public $tag    = null;

    /** @var string */
    public $initiator = null;

    /** @var Motion [] */
    public $allMotions;

    /** @var Amendment[] */
    public $allAmendments;

    /** @var Consultation */
    public $consultation;

    /** @var string */
    public $title = null;

    /** @var int */
    public $sort = 0;

    /**
     * @param Consultation $consultation
     * @param Motion[] $allMotions
     * @param bool $amendments
     */
    public function __construct(Consultation $consultation, $allMotions, $amendments)
    {
        parent::__construct();
        $this->consultation  = $consultation;
        $this->allMotions    = [];
        $this->allAmendments = [];
        foreach ($allMotions as $motion) {
            if ($motion->status != Motion::STATUS_DELETED) {
                $this->allMotions[] = $motion;
                if ($amendments) {
                    foreach ($motion->amendments as $amend) {
                        if ($amend->status != Amendment::STATUS_DELETED) {
                            $this->allAmendments[] = $amend;
                        }
                    }
                }
            }
        }
    }


    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['status', 'tag', 'sort'], 'number'],
            [['status', 'tag', 'title', 'initiator'], 'safe'],
        ];
    }

    /**
     * @param array $values
     * @param bool $safeOnly
     */
    public function setAttributes($values, $safeOnly = true)
    {
        parent::setAttributes($values, $safeOnly);
        $this->status = (isset($values['status']) && $values['status'] != '' ? IntVal($values['status']) : null);
    }

    /**
     * @param IMotion $motion1
     * @param IMotion $motion2
     * @return IMotion[]
     */
    public function sortDefault($motion1, $motion2)
    {
        if (is_a($motion1, Motion::class) && is_a($motion2, Amendment::class)) {
            return -1;
        }
        if (is_a($motion1, Amendment::class) && is_a($motion2, Motion::class)) {
            return 1;
        }
        if ($motion1->id < $motion2->id) {
            return -1;
        }
        if ($motion1->id > $motion2->id) {
            return 1;
        }
        return 0;
    }

    /**
     * @param IMotion $motion1
     * @param IMotion $motion2
     * @return IMotion[]
     */
    public function sortStatus($motion1, $motion2)
    {
        if ($motion1->status < $motion2->status) {
            return -1;
        }
        if ($motion1->status > $motion2->status) {
            return 1;
        }
        return 0;
    }

    /**
     * @param IMotion $motion1
     * @param IMotion $motion2
     * @return IMotion[]
     */
    public function sortTitle($motion1, $motion2)
    {
        if (is_a($motion1, Motion::class)) {
            /** @var Motion $motion1 */
            $title1 = $motion1->title;
        } else {
            /** @var Amendment $motion1 */
            $title1 = $motion1->getMyMotion()->title;
        }
        if (is_a($motion2, Motion::class)) {
            /** @var Motion $motion2 */
            $title2 = $motion2->title;
        } else {
            /** @var Amendment $motion2 */
            $title2 = $motion2->getMyMotion()->title;
        }
        return strnatcasecmp($title1, $title2);
    }

    /**
     * @param IMotion $motion1
     * @param IMotion $motion2
     * @return IMotion[]
     */
    public function sortTitlePrefix($motion1, $motion2)
    {
        if (is_a($motion1, Motion::class)) {
            /** @var Motion $motion1 */
            $rev1 = $motion1->titlePrefix;
        } else {
            /** @var Amendment $motion1 */
            $rev1 = $motion1->titlePrefix . ' zu ' . $motion1->getMyMotion()->titlePrefix;
        }
        if (is_a($motion2, Motion::class)) {
            /** @var Motion $motion2 */
            $rev2 = $motion2->titlePrefix;
        } else {
            /** @var Amendment $motion2 */
            $rev2 = $motion2->titlePrefix . ' zu ' . $motion2->getMyMotion()->titlePrefix;
        }

        return strnatcasecmp($rev1, $rev2);
    }

    /**
     * @param IMotion $motion1
     * @param IMotion $motion2
     * @return IMotion[]
     */
    public function sortInitiator($motion1, $motion2)
    {
        $init1 = $motion1->getInitiatorsStr();
        $init2 = $motion2->getInitiatorsStr();
        return strnatcasecmp($init1, $init2);
    }

    /**
     * @param IMotion $motion1
     * @param IMotion $motion2
     * @return IMotion[]
     */
    public function sortTag($motion1, $motion2)
    {
        if (is_a($motion1, Motion::class)) {
            /** @var Motion $motion1 */
            if (count($motion1->tags) > 0) {
                $tag1 = $motion1->tags[0];
            } else {
                $tag1 = null;
            }
        } else {
            $tag1 = null;
        }
        if (is_a($motion2, Motion::class)) {
            /** @var Motion $motion2 */
            if (count($motion2->tags) > 0) {
                $tag2 = $motion2->tags[0];
            } else {
                $tag2 = null;
            }
        } else {
            $tag2 = null;
        }
        if ($tag1 === null && $tag2 === null) {
            return 0;
        } elseif ($tag1 === null) {
            return 1;
        } elseif ($tag2 === null) {
            return -1;
        } else {
            return strnatcasecmp($tag1->title, $tag2->title);
        }
    }

    /**
     * @return IMotion[]
     */
    public function getSorted()
    {
        $merge = array_merge($this->getFilteredMotions(), $this->getFilteredAmendments());
        switch ($this->sort) {
            case static::SORT_TITLE:
                usort($merge, [static::class, 'sortTitle']);
                break;
            case static::SORT_STATUS:
                usort($merge, [static::class, 'sortStatus']);
                break;
            case static::SORT_TITLE_PREFIX:
                usort($merge, [static::class, 'sortTitlePrefix']);
                break;
            case static::SORT_INITIATOR:
                usort($merge, [static::class, 'sortInitiator']);
                break;
            case static::SORT_TAG:
                usort($merge, [static::class, 'sortTag']);
                break;
            case static::SORT_TYPE:
            default:
                usort($merge, [static::class, 'sortDefault']);
        }
        return $merge;
    }

    /**
     * @param Motion $motion
     * @return bool
     */
    private function motionMatchesInitiator(Motion $motion)
    {
        if ($this->initiator === null || $this->initiator == '') {
            return true;
        }
        foreach ($motion->motionSupporters as $supp) {
            if ($supp->personType == \app\models\db\ISupporter::PERSON_ORGANIZATION) {
                $name = $supp->organization;
            } else {
                $name = $supp->name;
            }
            if ($supp->role == MotionSupporter::ROLE_INITIATOR && mb_stripos($name, $this->initiator) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Motion $motion
     * @return bool
     */
    private function motionMatchesTag(Motion $motion)
    {
        if ($this->tag === null || $this->tag == 0) {
            return true;
        }
        foreach ($motion->tags as $tag) {
            if ($tag->id == $this->tag) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Motion[]
     */
    public function getFilteredMotions()
    {
        $out = [];
        foreach ($this->allMotions as $motion) {
            $matches = true;

            if ($this->status !== null && $this->status !== '' && $motion->status != $this->status) {
                $matches = false;
            }

            if (!$this->motionMatchesTag($motion)) {
                $matches = false;
            }

            if (!$this->motionMatchesInitiator($motion)) {
                $matches = false;
            }

            if ($this->title !== null && $this->title != '' && mb_stripos($motion->title, $this->title) === false) {
                $matches = false;
            }

            if ($matches) {
                $out[] = $motion;
            }
        }
        return $out;
    }


    /**
     * @param Amendment $amendment
     * @return bool
     */
    private function amendmentMatchInitiator(Amendment $amendment)
    {
        if ($this->initiator === null || $this->initiator == '') {
            return true;
        }
        foreach ($amendment->amendmentSupporters as $supp) {
            if ($supp->personType == \app\models\db\ISupporter::PERSON_ORGANIZATION) {
                $name = $supp->organization;
            } else {
                $name = $supp->name;
            }
            if ($supp->role == AmendmentSupporter::ROLE_INITIATOR && mb_stripos($name, $this->initiator) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Amendment $amendment
     * @return bool
     */
    private function amendmentMatchesTag(Amendment $amendment)
    {
        if ($this->tag === null || $this->tag == 0) {
            return true;
        }
        foreach ($amendment->getMyMotion()->tags as $tag) {
            if ($tag->id == $this->tag) {
                return true;
            }
        }
        return false;
    }


    /**
     * @return Amendment[]
     */
    public function getFilteredAmendments()
    {
        $out = [];
        foreach ($this->allAmendments as $amend) {
            $matches = true;

            if ($this->status !== null && $this->status !== "" && $amend->status != $this->status) {
                $matches = false;
            }

            if (!$this->amendmentMatchesTag($amend)) {
                $matches = false;
            }

            if (!$this->amendmentMatchInitiator($amend)) {
                $matches = false;
            }

            if ($this->title !== null && $this->title != '' && !mb_stripos($amend->getMyMotion()->title, $this->title)) {
                $matches = false;
            }

            if ($matches) {
                $out[] = $amend;
            }
        }
        return $out;
    }

    /**
     * @return string
     */
    public function getFilterFormFields()
    {
        $str = '';

        $str .= '<label>Titel:<br>';
        $title = Html::encode($this->title);
        $str .= '<input type="text" name="Search[title]" value="' . $title . '" class="form-control">';
        $str .= '</label>';

        $str .= '<label>Status:<br>';
        $stati       = ['' => '- egal -'];
        $foundMyself = false;
        foreach ($this->getStatusList() as $statusId => $statusName) {
            $stati[$statusId] = $statusName;
            if ($this->status !== null && $this->status == $statusId) {
                $foundMyself = true;
            }
        }
        if (!$foundMyself && $this->status !== null) {
            $stati                = Motion::getStati();
            $stati[$this->status] = Html::encode($stati[$this->status] . ' (0)');

        }
        $str .= HTMLTools::fueluxSelectbox('Search[status]', $stati, $this->status);
        $str .= '</label>';

        $tagsList = $this->getTagList();
        if (count($tagsList) > 0) {
            $name = 'Schlagwort:';
            $str .= '<label>' . $name . '<br>';
            $tags = ['' => '- egal -'];
            foreach ($tagsList as $tagId => $tagName) {
                $tags[$tagId] = $tagName;
            }
            $str .= HTMLTools::fueluxSelectbox('Search[tag]', $tags, $this->tag);
            $str .= '</label>';
        }

        $str .= '<div>';
        $str .= '<label for="initiatorSelect" style="margin-bottom: 0;">AntragstellerInnen:</label><br>';

        $values        = [];
        $initiatorList = $this->getInitiatorList();
        foreach (array_keys($initiatorList) as $initiatorName) {
            $values[] = $initiatorName;
        }

        $str .= '<div>
            <input id="initiatorSelect" class="typeahead form-control" type="text" placeholder="AntragstellerIn"
                name="Search[initiator]" value="' . Html::encode($this->initiator) . '"
                data-values="' . Html::encode(json_encode($values)) . '"></div>';
        $str .= '</div>';

        return $str;
    }

    /**
     * @return array
     */
    public function getStatusList()
    {
        $out = $num = [];
        foreach ($this->allMotions as $motion) {
            if (!isset($num[$motion->status])) {
                $num[$motion->status] = 0;
            }
            $num[$motion->status]++;
        }
        foreach ($this->allAmendments as $amend) {
            if (!isset($num[$amend->status])) {
                $num[$amend->status] = 0;
            }
            $num[$amend->status]++;
        }
        $stati = Motion::getStati();
        foreach ($stati as $statusId => $statusName) {
            if (isset($num[$statusId])) {
                $out[$statusId] = $statusName . ' (' . $num[$statusId] . ')';
            }
        }
        return $out;
    }


    /**
     * @return array
     */
    public function getTagList()
    {
        $tags = $tagsNames = [];
        foreach ($this->allMotions as $motion) {
            foreach ($motion->tags as $tag) {
                if (!isset($tags[$tag->id])) {
                    $tags[$tag->id]      = 0;
                    $tagsNames[$tag->id] = $tag->title;
                }
                $tags[$tag->id]++;
            }
        }
        foreach ($this->allAmendments as $amend) {
            foreach ($amend->getMyMotion()->tags as $tag) {
                if (!isset($tags[$tag->id])) {
                    $tags[$tag->id]      = 0;
                    $tagsNames[$tag->id] = $tag->title;
                }
                $tags[$tag->id]++;
            }
        }
        $out = [];
        foreach ($tags as $tagId => $num) {
            $out[$tagId] = $tagsNames[$tagId] . ' (' . $num . ')';
        }
        asort($out);
        return $out;
    }

    /**
     * @return array
     */
    public function getInitiatorList()
    {
        $initiators = [];
        foreach ($this->allMotions as $motion) {
            foreach ($motion->motionSupporters as $supp) {
                if ($supp->role != MotionSupporter::ROLE_INITIATOR) {
                    continue;
                }
                if ($supp->personType == ISupporter::PERSON_NATURAL) {
                    $name = $supp->name;
                } else {
                    $name = $supp->organization;
                }
                if (!isset($initiators[$name])) {
                    $initiators[$name] = 0;
                }
                $initiators[$name]++;
            }
        }
        foreach ($this->allAmendments as $amend) {
            foreach ($amend->amendmentSupporters as $supp) {
                if ($supp->role != AmendmentSupporter::ROLE_INITIATOR) {
                    continue;
                }
                if ($supp->personType == ISupporter::PERSON_NATURAL) {
                    $name = $supp->name;
                } else {
                    $name = $supp->organization;
                }
                if (!isset($initiators[$name])) {
                    $initiators[$name] = 0;
                }
                $initiators[$name]++;
            }
        }
        $out = [];
        foreach ($initiators as $name => $num) {
            $out[$name] = $name . ' (' . $num . ')';
        }
        asort($out);
        return $out;
    }

    /**
     * @param string $baseUrl
     * @param array $add
     * @return string
     */
    public function getCurrentUrl($baseUrl, $add = [])
    {
        return UrlHelper::createUrl(array_merge([$baseUrl], [
            'Search[status]'    => $this->status,
            'Search[tag]'       => $this->tag,
            'Search[initiator]' => $this->initiator,
            'Search[title]'     => $this->title,
            'Search[sort]'      => $this->sort,
        ], $add));
    }
}
