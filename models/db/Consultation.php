<?php

namespace app\models\db;

use app\components\MotionSorter;
use app\models\exceptions\DB;
use app\models\forms\SiteCreateForm;
use app\models\initiatorForms\DefaultForm;
use app\models\policies\IPolicy;
use app\models\sitePresets\ISitePreset;
use app\models\wording\Wording;
use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $siteId
 * @property int $type
 *
 * @property string $urlPath
 * @property string $title
 * @property string $titleShort
 * @property string $eventDateFrom
 * @property string $eventDateTo
 * @property string $deadlineMotions
 * @property string $deadlineAmendments
 * @property string $policyMotions
 * @property string $policyAmendments
 * @property string $policyComments
 * @property string $policySupport
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
 * @property ConsultationSettingsMotionSection[] $motionSections
 * @property ConsultationSettingsMotionType[] $motionTypes
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
            [['title', 'policyMotions', 'policyAmendments', 'policyComments', 'policySupport'], 'required'],
            [['title', 'titleShort', 'eventDateFrom', 'eventDateTo', 'adminEmail'], 'safe'],
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
        return $this->hasMany(Motion::className(), ['consultationId' => 'id']);
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
    public function getMotionSections()
    {
        return $this->hasMany(ConsultationSettingsMotionSection::className(), ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionTypes()
    {
        return $this->hasMany(ConsultationSettingsMotionType::className(), ['consultationId' => 'id']);
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
     * @param SiteCreateForm $form
     * @param Site $site
     * @param User $currentUser
     * @param ISitePreset $preset
     * @return Consultation
     * @throws DB
     */
    public static function createFromForm(SiteCreateForm $form, Site $site, User $currentUser, ISitePreset $preset)
    {
        $con             = new Consultation();
        $con->siteId     = $site->id;
        $con->title      = $form->title;
        $con->titleShort = $form->title;
        $con->type       = $form->preset;
        $con->urlPath    = $form->subdomain;
        $con->adminEmail = $currentUser->email;

        $settings                   = $con->getSettings();
        $settings->maintainanceMode = !$form->openNow;
        $con->setSettings($settings);

        $preset::setConsultationSettings($con);

        if (!$con->save()) {
            throw new DB($con->getErrors());
        }
        return $con;
    }

    /**
     * @param User $person
     * @return bool
     */
    public function isAdmin($person)
    {
        foreach ($this->admins as $e) {
            if ($e->id == $person->id) {
                return true;
            }
        }
        return $this->site->isAdmin($person);
    }

    /**
     * @return bool
     */
    public function isAdminCurUser()
    {
        $user = \Yii::$app->user;
        if ($user->isGuest) {
            return false;
        }
        $myself = User::findOne(["auth" => $user->id]);
        /** @var User $myself */
        if ($myself == null) {
            return false;
        }
        return $this->isAdmin($myself);
    }

    /**
     * @return array|array[]
     */
    public function getSortedMotions()
    {
        return MotionSorter::getSortedMotions($this, $this->motions, $this->getSettings()->amendNumberingByLine);
    }

    /**
     * @return Wording
     */
    public function getWording()
    {
        // @TODO
        return new Wording();
    }

    /**
     * @return IPolicy
     */
    public function getMotionPolicy()
    {
        return IPolicy::getInstanceByID($this->policyMotions, $this);
    }

    /**
     * @return IPolicy
     */
    public function getAmendmentPolicy()
    {
        return IPolicy::getInstanceByID($this->policyAmendments, $this);
    }

    /**
     * @return DefaultForm
     */
    public function getMotionInitiatorFormClass()
    {
        return new DefaultForm($this);
    }

    /**
     * @return DefaultForm
     */
    public function getAmendmentInitiatorFormClass()
    {
        return new DefaultForm($this);
    }

    /**
     * @return bool
     */
    public function motionDeadlineIsOver()
    {
        $normalized = str_replace(array(" ", ":", "-"), array("", "", ""), $this->deadlineMotions);
        if ($this->deadlineMotions != "" && date("YmdHis") > $normalized) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function amendmentDeadlineIsOver()
    {
        $normalized = str_replace(array(" ", ":", "-"), array("", "", ""), $this->deadlineAmendments);
        if ($this->deadlineAmendments != "" && date("YmdHis") > $normalized) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return int[]
     */
    public function getInvisibleMotionStati()
    {
        if ($this->getSettings()->screeningMotionsShown) {
            return [Motion::STATUS_DELETED, Motion::STATUS_UNCONFIRMED];
        } else {
            return [Motion::STATUS_DELETED, Motion::STATUS_UNCONFIRMED, Motion::STATUS_SUBMITTED_UNSCREENED];
        }
    }

    /**
     * @return int[]
     */
    public function getInvisibleAmendmentStati()
    {
        return $this->getInvisibleMotionStati();
    }
}
