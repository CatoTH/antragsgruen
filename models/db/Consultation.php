<?php

namespace app\models\db;

use app\models\ConsultationSettings;
use app\models\exceptions\DB;
use app\models\forms\SiteCreateForm;
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
        return $this->hasMany(ConsultationOdtTemplate::className(), ['id' => 'consultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubscriptions()
    {
        return $this->hasMany(ConsultationSubscription::className(), ['id' => 'consultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(ConsultationSettingsTag::className(), ['id' => 'consultationId']);
    }


    /** @var null|ConsultationSettings */
    private $settingsObject = null;

    /**
     * @return ConsultationSettings
     */
    public function getSettings()
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new ConsultationSettings($this->settings);
        }
        return $this->settingsObject;
    }

    /**
     * @param ConsultationSettings $settings
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
     * @return Consultation
     * @throws DB
     */
    public static function createFromForm(SiteCreateForm $form, Site $site, User $currentUser)
    {
        $con             = new Consultation();
        $con->siteId     = $site->id;
        $con->title      = $form->title;
        $con->type       = $form->preset;
        $con->urlPath    = $form->subdomain;
        $con->adminEmail = $currentUser->email;

        $settings                   = $con->getSettings();
        $settings->maintainanceMode = !$form->openNow;
        $con->setSettings($settings);

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
     * @param string $k1
     * @param string $k2
     * @return int
     */
    private function getSortedMotionsSort($k1, $k2)
    {
        if ($k1 == "" && $k2 == "") {
            return 0;
        }
        if ($k1 == "") {
            return -1;
        }
        if ($k2 == "") {
            return 1;
        }

        $cmp = function ($str1, $str2, $num1, $num2) {
            if ($str1 == $str2) {
                if ($num1 < $num2) {
                    return -1;
                }
                if ($num1 > $num2) {
                    return 1;
                }
                return 0;
            } else {
                if ($str1 < $str2) {
                    return -1;
                }
                if ($str1 > $str2) {
                    return 1;
                }
                return 0;
            }
        };
        $k1  = preg_replace("/neu$/siu", "neu1", $k1);
        $k2  = preg_replace("/neu$/siu", "neu1", $k2);

        $pat1 = "/^(?<str1>[^0-9]*)(?<num1>[0-9]*)/siu";
        $pat2 = "/^(?<str1>[^0-9]*)(?<num1>[0-9]+)(?<str2>[^0-9]+)(?<num2>[0-9]+)$/siu";

        if (preg_match($pat2, $k1, $matches1) && preg_match($pat2, $k2, $matches2)) {
            if ($matches1["str1"] == $matches2["str1"] && $matches1["num1"] == $matches2["num1"]) {
                return $cmp($matches1["str2"], $matches2["str2"], $matches1["num2"], $matches2["num2"]);
            } else {
                return $cmp($matches1["str1"], $matches2["str1"], $matches1["num1"], $matches2["num1"]);
            }
        } elseif (preg_match($pat2, $k1, $matches1) && preg_match($pat1, $k2, $matches2)) {
            if ($matches1["str1"] == $matches2["str1"] && $matches1["num1"] == $matches2["num1"]) {
                return 1;
            } else {
                return $cmp($matches1["str1"], $matches2["str1"], $matches1["num1"], $matches2["num1"]);
            }
        } elseif (preg_match($pat1, $k1, $matches1) && preg_match($pat2, $k2, $matches2)) {
            if ($matches1["str1"] == $matches2["str1"] && $matches1["num1"] == $matches2["num1"]) {
                return -1;
            } else {
                return $cmp($matches1["str1"], $matches2["str1"], $matches1["num1"], $matches2["num1"]);
            }
        } else {
            preg_match($pat1, $k1, $matches1);
            preg_match($pat1, $k2, $matches2);
            $str1 = (isset($matches1["str1"]) ? $matches1["str1"] : "");
            $str2 = (isset($matches2["str1"]) ? $matches2["str1"] : "");
            $num1 = (isset($matches1["num1"]) ? $matches1["num1"] : "");
            $num2 = (isset($matches2["num1"]) ? $matches2["num1"] : "");
            return $cmp($str1, $str2, $num1, $num2);
        }
    }


    /**
     * @return array|array[]
     */
    public function getSortedMotions()
    {
        $motions       = $this->motions;
        $motionsSorted = array();

        $inivisible   = IMotion::getInvisibleStati();
        $inivisible[] = IMotion::STATUS_MODIFIED;

        foreach ($motions as $motion) {
            if (!in_array($motion->status, $inivisible)) {
                //$motion->tags // @TODO
                $typeName = "";

                if (!isset($motionsSorted[$typeName])) {
                    $motionsSorted[$typeName] = array();
                }
                $key = $motion->titlePrefix;

                // @TODO veranstaltungsspezifisch_ae_sortierung_zeilennummer noch nötig ?
                if ($this->getSettings()->amendNumberingByLine) {
                    $motion->amendments = Amendment::sortVisibleByLineNumbers($motion->amendments);
                }

                $motionsSorted[$typeName][$key] = $motion;
            }
        }

        foreach (array_keys($motionsSorted) as $key) {
            uksort($motionsSorted[$key], array($this, "getSortedMotionsSort"));
        }

        return $motionsSorted;
    }

    /**
     * @return Wording
     */
    public function getWording()
    {
        // @TODO
        return new Wording();
    }
}
