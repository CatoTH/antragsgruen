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
    const SORT_STATUS = 1;
    const SORT_TITLE = 2;
    const SORT_TITLE_PREFIX = 3;
    const SORT_INITIATOR = 4;
    const SORT_TAG = 5;
    const SORT_PUBLICATION = 6;
    const SORT_PROPOSAL = 7;
    const SORT_PROPOSAL_STATUS = 8;
    const SORT_RESPONSIBILITY = 9;

    /** @var int */
    public $status = null;
    public $tag = null;
    public $agendaItem = null;
    public $proposalStatus = null;
    public $responsibility = null;

    /** @var string */
    public $initiator = null;
    public $title = null;
    public $prefix = null;

    /** @var Motion [] */
    public $allMotions;

    /** @var Amendment[] */
    public $allAmendments;

    /** @var Consultation */
    public $consultation;

    /** @var int */
    public $sort = 3;

    /** @var boolean */
    private $showScreening;

    /**
     * @param Consultation $consultation
     * @param Motion[] $allMotions
     * @param bool $amendments
     * @param boolean $showScreening
     */
    public function __construct(Consultation $consultation, $allMotions, $amendments, $showScreening)
    {
        parent::__construct();
        $this->showScreening = $showScreening;
        $this->consultation  = $consultation;
        $this->allMotions    = [];
        $this->allAmendments = [];
        foreach ($allMotions as $motion) {
            if ($this->isVisible($motion)) {
                $this->allMotions[] = $motion;
                if ($amendments) {
                    foreach ($motion->amendments as $amend) {
                        if ($this->isVisible($amend)) {
                            $this->allAmendments[] = $amend;
                        }
                    }
                }
            }
        }
    }

    private function isVisible(IMotion $entry): bool
    {
        if ($this->showScreening) {
            return $entry->isVisibleForAdmins();
        } else {
            return $entry->isVisibleForProposalAdmins();
        }
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['status', 'tag', 'sort', 'agendaItem', 'responsibility'], 'number'],
            [['status', 'proposalStatus', 'tag', 'title', 'initiator', 'agendaItem', 'responsibility', 'prefix'], 'safe'],
        ];
    }

    /**
     * @param array $values
     * @param bool $safeOnly
     */
    public function setAttributes($values, $safeOnly = true)
    {
        parent::setAttributes($values, $safeOnly);
        if (isset($values['sort'])) {
            $this->sort = intval($values['sort']);
        }
        if (isset($values['status'])) {
            $this->status = ($values['status'] === '' ? null : intval($values['status']));
        }
        if (isset($values['tag'])) {
            $this->tag = ($values['tag'] === '' ? null : intval($values['tag']));
        }
        if (isset($values['responsibility'])) {
            $this->responsibility = ($values['responsibility'] === '' ? null : intval($values['responsibility']));
        }

        if (isset($values['proposalStatus']) && $values['proposalStatus'] != '') {
            $this->proposalStatus = $values['proposalStatus'];
        } else {
            $this->proposalStatus = null;
        }
    }

    public function sortDefault(IMotion $motion1, IMotion $motion2): int
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

    public function sortStatus(IMotion $motion1, IMotion $motion2): int
    {
        if ($motion1->status < $motion2->status) {
            return -1;
        }
        if ($motion1->status > $motion2->status) {
            return 1;
        }

        return 0;
    }

    public function sortProposalStatus(IMotion $motion1, IMotion $motion2): int
    {
        $status1 = (is_a($motion1, Amendment::class) ? $motion1->proposalStatus : 0);
        $status2 = (is_a($motion2, Amendment::class) ? $motion2->proposalStatus : 0);
        if ($status1 < $status2) {
            return -1;
        }
        if ($status1 > $status2) {
            return 1;
        }

        return 0;
    }

    private function normalizeResponsibility(IMotion $motion): string
    {
        $parts = [];
        if ($motion->responsibilityUser) {
            if ($motion->responsibilityUser->name) {
                $parts[] = trim($motion->responsibilityUser->name);
            } else {
                $parts[] = trim($motion->responsibilityUser->getAuthName());
            }
        }
        if ($motion->responsibilityComment) {
            $parts[] = trim($motion->responsibilityComment);
        }

        return trim(implode(' ', $parts));
    }

    public function sortResponsibility(IMotion $motion1, IMotion $motion2): int
    {
        $responsibility1 = $this->normalizeResponsibility($motion1);
        $responsibility2 = $this->normalizeResponsibility($motion2);

        if ($responsibility1 && $responsibility2) {
            return strnatcasecmp($responsibility1, $responsibility2);
        } elseif ($responsibility1) {
            return -1;
        } elseif ($responsibility2) {
            return 1;
        } else {
            return 0;
        }
    }

    public function sortTitle(IMotion $motion1, IMotion $motion2): int
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
        $cmp = strnatcasecmp($title1, $title2);
        if ($cmp === 0) {
            return ($motion1->id < $motion2->id ? 1 : -1);
        } else {
            return $cmp;
        }
    }

    public function sortTitlePrefix(IMotion $motion1, IMotion $motion2): int
    {
        if (is_a($motion1, Motion::class)) {
            /** @var Motion $motion1 */
            $rev1 = $motion1->titlePrefix;
        } else {
            /** @var Amendment $motion1 */
            $rev1 = $motion1->titlePrefix . ' ' . \Yii::t('amend', 'amend_for_motion') .
                    ' ' . $motion1->getMyMotion()->titlePrefix;
        }
        if (is_a($motion2, Motion::class)) {
            /** @var Motion $motion2 */
            $rev2 = $motion2->titlePrefix;
        } else {
            /** @var Amendment $motion2 */
            $rev2 = $motion2->titlePrefix . ' ' . \Yii::t('amend', 'amend_for_motion') .
                    ' ' . $motion2->getMyMotion()->titlePrefix;
        }

        return strnatcasecmp($rev1, $rev2);
    }

    public function sortInitiator(IMotion $motion1, IMotion $motion2): int
    {
        $init1 = $motion1->getInitiatorsStr();
        $init2 = $motion2->getInitiatorsStr();
        $cmp   = strnatcasecmp($init1, $init2);
        if ($cmp === 0) {
            return $this->sortTitlePrefix($motion1, $motion2);
        } else {
            return $cmp;
        }
    }

    public function sortTag(IMotion $motion1, IMotion $motion2): int
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
            $cmp = strnatcasecmp($tag1->title, $tag2->title);
            if ($cmp === 0) {
                return $this->sortTitlePrefix($motion1, $motion2);
            } else {
                return $cmp;
            }
        }
    }

    /**
     * @param IMotion[] $entries
     *
     * @return IMotion[]
     */
    public function moveAmendmentsToMotions($entries)
    {
        $foundMotions = [];
        foreach ($entries as $entry) {
            if (is_a($entry, Motion::class)) {
                $foundMotions[] = $entry->id;
            }
        }
        /** @var IMotion[] $newArr1 */
        $newArr1 = [];
        /** @var Amendment[] $movingAmendments */
        $movingAmendments = [];
        foreach ($entries as $entry) {
            if (is_a($entry, Amendment::class)) {
                /** @var Amendment $entry */
                if (in_array($entry->motionId, $foundMotions)) {
                    $movingAmendments[] = $entry;
                } else {
                    $newArr1[] = $entry;
                }
            } else {
                $newArr1[] = $entry;
            }
        }
        /** @var IMotion[] $result */
        $result = [];
        foreach ($newArr1 as $entry) {
            $result[] = $entry;
            if (is_a($entry, Motion::class)) {
                foreach ($movingAmendments as $amendment) {
                    if ($amendment->motionId === $entry->id) {
                        $result[] = $amendment;
                    }
                }
            }
        }

        return $result;
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
            case static::SORT_PROPOSAL_STATUS:
                usort($merge, [static::class, 'sortProposalStatus']);
                break;
            case static::SORT_RESPONSIBILITY:
                usort($merge, [static::class, 'sortResponsibility']);
                break;
            default:
                usort($merge, [static::class, 'sortTitlePrefix']);
        }
        if (!in_array($this->sort, [static::SORT_STATUS, static::SORT_INITIATOR, static::SORT_TAG])) {
            $merge = $this->moveAmendmentsToMotions($merge);
        }

        return $merge;
    }

    private function motionMatchesInitiator(Motion $motion): bool
    {
        if ($this->initiator === null || $this->initiator === '') {
            return true;
        }
        foreach ($motion->motionSupporters as $supp) {
            if ($supp->personType === ISupporter::PERSON_ORGANIZATION) {
                $name = $supp->organization;
            } else {
                $name = $supp->name;
            }
            if ($supp->role === MotionSupporter::ROLE_INITIATOR && mb_stripos($name, $this->initiator) !== false) {
                return true;
            }
        }

        return false;
    }

    private function motionMatchesTag(Motion $motion): bool
    {
        if ($this->tag === null || $this->tag === 0) {
            return true;
        }
        foreach ($motion->tags as $tag) {
            if ($tag->id === $this->tag) {
                return true;
            }
        }

        return false;
    }

    private function motionMatchesAgendaItem(Motion $motion): bool
    {
        if ($this->agendaItem === null || $this->agendaItem === 0) {
            return true;
        }

        return ($motion->agendaItemId === $this->agendaItem);
    }

    private function motionMatchesResponsibility(Motion $motion): bool
    {
        if ($this->responsibility === null || $this->responsibility === 0) {
            return true;
        }

        return ($motion->responsibilityId === $this->responsibility);
    }

    /**
     * @return Motion[]
     */
    public function getFilteredMotions()
    {
        $out = [];
        foreach ($this->allMotions as $motion) {
            $matches = true;

            if ($this->status !== null && $this->status !== '' && $motion->status !== $this->status) {
                $matches = false;
            }

            if ($this->proposalStatus !== null && $this->proposalStatus !== '') {
                $matches = false;
            }

            if (!$this->motionMatchesTag($motion)) {
                $matches = false;
            }

            if (!$this->motionMatchesInitiator($motion)) {
                $matches = false;
            }

            if (!$this->motionMatchesAgendaItem($motion)) {
                $matches = false;
            }

            if (!$this->motionMatchesResponsibility($motion)) {
                $matches = false;
            }

            if ($this->title !== null && $this->title !== '' && mb_stripos($motion->title, $this->title) === false) {
                $matches = false;
            }

            $prefix = $this->prefix;
            if ($prefix !== null && $prefix !== '' && mb_stripos($motion->titlePrefix, $prefix) === false) {
                $matches = false;
            }

            if ($matches) {
                $out[] = $motion;
            }
        }

        return $out;
    }


    private function amendmentMatchInitiator(Amendment $amendment): bool
    {
        if ($this->initiator === null || $this->initiator === '') {
            return true;
        }
        foreach ($amendment->amendmentSupporters as $supp) {
            if ($supp->personType === ISupporter::PERSON_ORGANIZATION) {
                $name = $supp->organization;
            } else {
                $name = $supp->name;
            }
            if ($supp->role === AmendmentSupporter::ROLE_INITIATOR && mb_stripos($name, $this->initiator) !== false) {
                return true;
            }
        }

        return false;
    }

    private function amendmentMatchesTag(Amendment $amendment): bool
    {
        if ($this->tag === null || $this->tag === 0) {
            return true;
        }
        foreach ($amendment->getMyMotion()->tags as $tag) {
            if ($tag->id === $this->tag) {
                return true;
            }
        }

        return false;
    }

    private function amendmentMatchesAgendaItem(Amendment $amendment): bool
    {
        if ($this->agendaItem === null || $this->agendaItem === 0) {
            return true;
        }

        return ($amendment->getMyMotion()->agendaItemId === $this->agendaItem);
    }

    private function amendmentMatchesResponsibility(Amendment $amendment): bool
    {
        if ($this->responsibility === null || $this->responsibility === 0) {
            return true;
        }

        return ($amendment->responsibilityId === $this->responsibility);
    }

    /**
     * @return Amendment[]
     */
    public function getFilteredAmendments()
    {
        $out = [];
        foreach ($this->allAmendments as $amend) {
            $matches = true;

            if ($this->status !== null && $this->status !== "" && $amend->status !== $this->status) {
                $matches = false;
            }

            if ($this->proposalStatus !== null && $this->proposalStatus !== '') {
                if ($this->proposalStatus == 'noresponse') {
                    if ($amend->proposalNotification === null ||
                        $amend->proposalUserStatus == Amendment::STATUS_ACCEPTED) {
                        $matches = false;
                    }
                } elseif ($this->proposalStatus === 'accepted') {
                    if ($amend->proposalNotification === null ||
                        $amend->proposalUserStatus !== Amendment::STATUS_ACCEPTED) {
                        $matches = false;
                    }
                } else {
                    if ($this->proposalStatus != $amend->proposalStatus) {
                        $matches = false;
                    }
                }
            }

            if (!$this->amendmentMatchesTag($amend)) {
                $matches = false;
            }

            if (!$this->amendmentMatchInitiator($amend)) {
                $matches = false;
            }

            if (!$this->amendmentMatchesAgendaItem($amend)) {
                $matches = false;
            }

            if (!$this->amendmentMatchesResponsibility($amend)) {
                $matches = false;
            }

            $title = $this->title;
            if ($title !== null && $title !== '' && mb_stripos($amend->getMyMotion()->title, $title) === false) {
                $matches = false;
            }

            $prefix = $this->prefix;
            if ($prefix !== null && $prefix !== '' && !mb_stripos($amend->titlePrefix, $prefix)) {
                $matches = false;
            }

            if ($matches) {
                $out[] = $amend;
            }
        }

        return $out;
    }

    public function getFilterFormFields(bool $responsibilities): string
    {
        $str = '';

        $str    .= '<label>' . \Yii::t('admin', 'filter_prefix') . ':<br>';
        $prefix = Html::encode($this->prefix);
        $str    .= '<input type="text" name="Search[prefix]" value="' . $prefix . '" class="form-control inputPrefix">';
        $str    .= '</label>';

        $str   .= '<label>' . \Yii::t('admin', 'filter_title') . ':<br>';
        $title = Html::encode($this->title);
        $str   .= '<input type="text" name="Search[title]" value="' . $title . '" class="form-control">';
        $str   .= '</label>';


        // Motion status

        $str         .= '<label>' . \Yii::t('admin', 'filter_status') . ':<br>';
        $statuses    = ['' => \Yii::t('admin', 'filter_na')];
        $foundMyself = false;
        foreach ($this->getStatusList() as $statusId => $statusName) {
            $statuses[$statusId] = $statusName;
            if ($this->status !== null && $this->status === $statusId) {
                $foundMyself = true;
            }
        }
        if (!$foundMyself && $this->status !== null) {
            $statusNames             = Motion::getStatusNames();
            $statuses[$this->status] = Html::encode($statusNames[$this->status] . ' (0)');
        }
        $str .= HTMLTools::fueluxSelectbox('Search[status]', $statuses, $this->status, [], true);
        $str .= '</label>';


        // Proposal status

        $str         .= '<label>' . \Yii::t('admin', 'filter_proposal_status') . ':<br>';
        $statuses    = ['' => \Yii::t('admin', 'filter_na')];
        $foundMyself = false;
        foreach ($this->getProposalStatusList() as $statusId => $statusName) {
            $statuses[$statusId] = $statusName;
            if ($this->proposalStatus !== null && $this->proposalStatus === $statusId) {
                $foundMyself = true;
            }
        }
        if (!$foundMyself && $this->proposalStatus !== null) {
            $statusNames             = Motion::getProposedStatusNames();
            $statuses[$this->status] = Html::encode($statusNames[$this->proposalStatus] . ' (0)');
        }
        $str .= HTMLTools::fueluxSelectbox('Search[proposalStatus]', $statuses, $this->proposalStatus, [], true);
        $str .= '</label>';


        // Tag List

        $tagsList = $this->getTagList();
        if (count($tagsList) > 0) {
            $name = \Yii::t('admin', 'filter_tag') . ':';
            $str  .= '<label>' . $name . '<br>';
            $tags = ['' => \Yii::t('admin', 'filter_na')];
            foreach ($tagsList as $tagId => $tagName) {
                $tags[$tagId] = $tagName;
            }
            $str .= HTMLTools::fueluxSelectbox('Search[tag]', $tags, $this->tag, [], true);
            $str .= '</label>';
        }


        // Agenda items

        $agendaItemList = $this->getAgendaItemList();
        if (count($agendaItemList) > 0) {
            $name  = \Yii::t('admin', 'filter_agenda_item') . ':';
            $str   .= '<label>' . $name . '<br>';
            $items = ['' => \Yii::t('admin', 'filter_na')];
            foreach ($agendaItemList as $itemId => $itemName) {
                $items[$itemId] = $itemName;
            }
            $str .= HTMLTools::fueluxSelectbox('Search[agendaItem]', $items, $this->agendaItem, [], true);
            $str .= '</label>';
        }


        // Responsibility

        if ($responsibilities) {
            $allResponsibilities = $this->getRespoinsibilityList();
            if (count($allResponsibilities) > 0) {
                $name  = \Yii::t('admin', 'filter_responsibility') . ':';
                $str   .= '<label>' . $name . '<br>';
                $items = ['' => \Yii::t('admin', 'filter_na')];
                foreach ($allResponsibilities as $itemId => $itemName) {
                    $items[$itemId] = $itemName;
                }
                $str .= HTMLTools::fueluxSelectbox('Search[responsibility]', $items, $this->responsibility, [], true);
                $str .= '</label>';
            }
        }


        // Initiators

        $str .= '<div>';
        $str .= '<label for="initiatorSelect" style="margin-bottom: 0;">' .
                \Yii::t('admin', 'filter_initiator') . ':</label><br>';

        $values        = [];
        $initiatorList = $this->getInitiatorList();
        foreach (array_keys($initiatorList) as $initiatorName) {
            $values[] = $initiatorName;
        }

        $str .= '<div>
            <input id="initiatorSelect" class="typeahead form-control" type="text"
                placeholder="' . \Yii::t('admin', 'filter_initiator_name') . '"
                name="Search[initiator]" value="' . Html::encode($this->initiator) . '"
                data-values="' . Html::encode(json_encode($values)) . '"></div>';
        $str .= '</div>';

        return $str;
    }

    public function getStatusList(): array
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
        $statuses = Motion::getStatusNames();
        foreach ($statuses as $statusId => $statusName) {
            if (isset($num[$statusId])) {
                $out[$statusId] = $statusName . ' (' . $num[$statusId] . ')';
            }
        }

        return $out;
    }

    public function getProposalStatusList(): array
    {
        $out         = $num = [];
        $numAccepted = $numNotResponded = 0;
        foreach ($this->allAmendments as $amend) {
            if (!isset($num[$amend->proposalStatus])) {
                $num[$amend->proposalStatus] = 0;
            }
            $num[$amend->proposalStatus]++;
            if ($amend->proposalNotification) {
                if ($amend->proposalUserStatus === Amendment::STATUS_ACCEPTED) {
                    $numAccepted++;
                } else {
                    $numNotResponded++;
                }
            }
        }
        $statuses = Amendment::getProposedStatusNames();
        foreach ($statuses as $statusId => $statusName) {
            if (isset($num[$statusId])) {
                $out[$statusId] = $statusName . ' (' . $num[$statusId] . ')';
            }
        }
        if ($numAccepted > 0) {
            $out['accepted'] = \Yii::t('admin', 'filter_proposal_accepted') . ' (' . $numAccepted . ')';
        }
        if ($numNotResponded > 0) {
            $out['noresponse'] = \Yii::t('admin', 'filter_proposal_noresponse') . ' (' . $numNotResponded . ')';
        }

        return $out;
    }


    public function getTagList(): array
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

    public function getAgendaItemList(): array
    {
        $agendaItems = [];
        foreach ($this->consultation->agendaItems as $agendaItem) {
            $num = count($agendaItem->motions);
            if ($num > 0) {
                $agendaItems[$agendaItem->id] = $agendaItem->title . ' (' . $num . ')';
            }
        }

        return $agendaItems;
    }

    public function getRespoinsibilityList(): array
    {
        $userNames = [];
        $userNum   = [];
        foreach (array_merge($this->allMotions, $this->allAmendments) as $imotion) {
            /** @var IMotion $imotion */
            if ($imotion->responsibilityUser) {
                if ($imotion->responsibilityUser->name) {
                    $name = $imotion->responsibilityUser->name;
                } else {
                    $name = $imotion->responsibilityUser->getAuthName();
                }
                $userNames[$imotion->responsibilityUser->id] = $name;
                if (!isset($userNum[$imotion->responsibilityUser->id])) {
                    $userNum[$imotion->responsibilityUser->id] = 0;
                }
                $userNum[$imotion->responsibilityUser->id]++;
            }
        }
        $out = [];
        foreach ($userNum as $userId => $num) {
            $out[$userId] = $userNames[$userId] . ' (' . $num . ')';
        }
        asort($out);

        return $out;
    }

    public function getInitiatorList(): array
    {
        $initiators = [];
        foreach ($this->allMotions as $motion) {
            foreach ($motion->motionSupporters as $supp) {
                if ($supp->role != MotionSupporter::ROLE_INITIATOR) {
                    continue;
                }
                if ($supp->personType === ISupporter::PERSON_NATURAL) {
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
                if ($supp->personType === ISupporter::PERSON_NATURAL) {
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

    public function getCurrentUrl(string $baseUrl, array $add = []): string
    {
        return UrlHelper::createUrl(array_merge([$baseUrl], [
            'Search[status]'         => $this->status,
            'Search[tag]'            => $this->tag,
            'Search[initiator]'      => $this->initiator,
            'Search[title]'          => $this->title,
            'Search[sort]'           => $this->sort,
            'Search[agendaItem]'     => $this->agendaItem,
            'Search[responsibility]' => $this->responsibility,
            'Search[prefix]'         => $this->prefix,
        ], $add));
    }
}
