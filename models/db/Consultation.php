<?php

namespace app\models\db;

use app\components\UrlHelper;
use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\exceptions\DB;
use app\models\exceptions\NotFound;
use app\models\forms\SiteCreateForm;
use app\models\SearchResult;
use app\models\sitePresets\ISitePreset;
use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $siteId
 * @property int $type
 * @property int $amendmentNumbering
 *
 * @property string $urlPath
 * @property string $title
 * @property string $titleShort
 * @property string $wordingBase
 * @property string $eventDateFrom
 * @property string $eventDateTo
 * @property string $adminEmail
 * @property string $settings
 *
 * @property Site $site
 * @property Motion[] $motions
 * @property ConsultationText[] $texts
 * @property User[] $admins
 * @property ConsultationOdtTemplate[] $odtTemplates
 * @property ConsultationSubscription[] $subscriptions
 * @property ConsultationSettingsTag[] $tags
 * @property ConsultationMotionType[] $motionTypes
 * @property ConsultationAgendaItem[] $agendaItems
 */
class Consultation extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'consultation';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['title', 'titleShort', 'eventDateFrom', 'eventDateTo'], 'safe'],
            [['adminEmail', 'wordingBase', 'amendmentNumbering'], 'safe'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->hasOne(Site::className(), ['id' => 'siteId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotions()
    {
        return $this->hasMany(Motion::className(), ['consultationId' => 'id'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    /**
     * @param int $motionId
     * @return Motion|null
     */
    public function getMotion($motionId)
    {
        foreach ($this->motions as $motion) {
            if ($motion->id == $motionId && $motion->status != Motion::STATUS_DELETED) {
                return $motion;
            }
        }
        return null;
    }

    /**
     * @param int $amendmentId
     * @return Amendment|null
     */
    public function getAmendment($amendmentId)
    {
        foreach ($this->motions as $motion) {
            if ($motion->status == Motion::STATUS_DELETED) {
                continue;
            }
            foreach ($motion->amendments as $amendment) {
                if ($amendment->id == $amendmentId && $amendment->status != Amendment::STATUS_DELETED) {
                    return $amendment;
                }
            }
        }
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTexts()
    {
        return $this->hasMany(ConsultationText::className(), ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdmins()
    {
        return $this->hasMany(User::className(), ['id' => 'userId'])
            ->viaTable('consultationAdmin', ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOdtTemplates()
    {
        return $this->hasMany(ConsultationOdtTemplate::className(), ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAgendaItems()
    {
        return $this->hasMany(ConsultationAgendaItem::className(), ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubscriptions()
    {
        return $this->hasMany(ConsultationSubscription::className(), ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(ConsultationSettingsTag::className(), ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionTypes()
    {
        return $this->hasMany(ConsultationMotionType::className(), ['consultationId' => 'id']);
    }

    /**
     * @param int $motionTypeId
     * @return ConsultationMotionType
     * @throws NotFound
     */
    public function getMotionType($motionTypeId)
    {
        foreach ($this->motionTypes as $motionType) {
            if ($motionType->id == $motionTypeId) {
                return $motionType;
            }
        }
        throw new NotFound('Motion Type not found');
    }

    /** @var null|\app\models\settings\Consultation */
    private $settingsObject = null;

    /**
     * @return \app\models\settings\Consultation
     */
    public function getSettings()
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new \app\models\settings\Consultation($this->settings);
        }
        return $this->settingsObject;
    }

    /**
     * @param \app\models\settings\Consultation $settings
     */
    public function setSettings($settings)
    {
        $this->settingsObject = $settings;
        $this->settings       = $settings->toJSON();
    }

    /**
     * @return IAmendmentNumbering
     */
    public function getAmendmentNumbering()
    {
        $numberings = IAmendmentNumbering::getNumberings();
        return new $numberings[$this->amendmentNumbering]();
    }


    /**
     * @param SiteCreateForm $form
     * @param Site $site
     * @param User $currentUser
     * @param ISitePreset $preset
     * @return Consultation
     * @throws DB
     */
    public static function createFromForm(SiteCreateForm $form, Site $site, User $currentUser, ISitePreset $preset)
    {
        $con                     = new Consultation();
        $con->siteId             = $site->id;
        $con->title              = $form->title;
        $con->titleShort         = $form->title;
        $con->type               = $form->preset;
        $con->urlPath            = $form->subdomain;
        $con->adminEmail         = $currentUser->email;
        $con->amendmentNumbering = 0;

        $settings                   = $con->getSettings();
        $settings->maintainanceMode = !$form->openNow;
        $con->setSettings($settings);

        $preset->setConsultationSettings($con);

        if (!$con->save()) {
            throw new DB($con->getErrors());
        }
        return $con;
    }

    /**
     * @param int $privilege
     * @return bool
     *
     */
    public function havePrivilege($privilege)
    {
        $user = User::getCurrentUser();
        if (!$user) {
            return false;
        }
        return $user->hasPrivilege($this, $privilege);
    }


    /**
     * @return ConsultationSettingsTag[]
     */
    public function getSortedTags()
    {
        $tags = $this->tags;
        usort(
            $tags,
            function ($tag1, $tag2) {
                /** @var ConsultationSettingsTag $tag1 */
                /** @var ConsultationSettingsTag $tag2 */
                if ($tag1->position < $tag2->position) {
                    return -1;
                }
                if ($tag1->position > $tag2->position) {
                    return 1;
                }
                return 0;
            }
        );
        return $tags;
    }

    /**
     * @return int[]
     */
    public function getInvisibleMotionStati()
    {
        $invisible = [Motion::STATUS_DELETED, Motion::STATUS_UNCONFIRMED, Motion::STATUS_DRAFT];
        if (!$this->getSettings()->screeningMotionsShown) {
            $invisible[] = Motion::STATUS_SUBMITTED_UNSCREENED;
        }
        return $invisible;
    }

    /**
     * @return int[]
     */
    public function getInvisibleAmendmentStati()
    {
        return $this->getInvisibleMotionStati();
    }

    /**
     * @param int $motionTypeId
     * @return string
     */
    public function getNextMotionPrefix($motionTypeId)
    {
        $max_rev = 0;
        /** @var ConsultationMotionType $motionType */
        $motionType = null;
        foreach ($this->motionTypes as $t) {
            if ($t->id == $motionTypeId) {
                $motionType = $t;
            }
        }
        $prefix = $motionType->motionPrefix;
        if ($prefix == '') {
            $prefix = 'A';
        }
        foreach ($this->motions as $motion) {
            if ($motion->status != Motion::STATUS_DELETED) {
                if (mb_substr($motion->titlePrefix, 0, mb_strlen($prefix)) !== $prefix) {
                    continue;
                }
                $revs  = mb_substr($motion->titlePrefix, mb_strlen($prefix));
                $revnr = IntVal($revs);
                if ($revnr > $max_rev) {
                    $max_rev = $revnr;
                }
            }
        }
        return $prefix . ($max_rev + 1);
    }

    /**
     * @param int $motionId
     * @return string
     */
    public function getNextAmendmentPrefix($motionId)
    {

        return 'TODO'; // @TODO
    }

    /**
     *
     */
    public function flushCaches()
    {
        // @TODO
    }


    /**
     * @param string $text
     * @param array $backParams
     * @return \app\models\SearchResult[]
     * @throws \app\models\exceptions\Internal
     */
    public function fulltextSearch($text, $backParams)
    {
        $results = [];
        foreach ($this->motions as $motion) {
            if (in_array($motion->status, $this->getInvisibleMotionStati())) {
                continue;
            }
            $found = false;
            foreach ($motion->sections as $section) {
                if (!$found && $section->getSectionType()->matchesFulltextSearch($text)) {
                    $found             = true;
                    $result            = new SearchResult();
                    $result->id        = 'motion' . $motion->id;
                    $result->typeTitle = $motion->motionType->titleSingular;
                    $result->type      = SearchResult::TYPE_MOTION;
                    $result->title     = $motion->getTitleWithPrefix();
                    $result->link      = UrlHelper::createMotionUrl($motion, 'view', $backParams);
                    $results[]         = $result;
                }
            }
            if (!$found) {
                foreach ($motion->amendments as $amend) {
                    if (in_array($amend->status, $this->getInvisibleAmendmentStati())) {
                        continue;
                    }
                    foreach ($amend->sections as $section) {
                        if (!$found && $section->getSectionType()->matchesFulltextSearch($text)) {
                            $found             = true;
                            $result            = new SearchResult();
                            $result->id        = 'amendment' . $amend->id;
                            $result->typeTitle = 'Änderungsantrag';
                            $result->type      = SearchResult::TYPE_AMENDMENT;
                            $result->title     = $amend->getTitle();
                            $result->link      = UrlHelper::createAmendmentUrl($amend, 'view', $backParams);
                            $results[]         = $result;
                        }
                    }
                }
            }
        }
        /*
         * @TODO: - Kommentare
         */
        return $results;
    }
}
