<?php
namespace app\models\db;

use app\models\AntragsgruenAppParams;
use app\models\exceptions\DB;
use app\models\forms\SiteCreateForm;
use app\models\SiteSettings;
use app\models\SiteSpecificBehavior;
use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $currentConsultationId
 * @property int $public
 * @property string $subdomain
 * @property string $title
 * @property string $titleShort
 * @property string $settings
 * @property string $contact
 *
 * @property Consultation $currentConsultation
 * @property Consultation[] $consultations
 * @property User[] $namespaceUsers
 * @property User[] $admins
 */
class Site extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'site';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurrentConsultation()
    {
        return $this->hasOne(Consultation::className(), ['id' => 'currentConsultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultations()
    {
        return $this->hasMany(Consultation::className(), ['siteId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNamespaceUsers()
    {
        return $this->hasMany(User::className(), ['siteNamespaceId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdmins()
    {
        return $this->hasMany(User::className(), ['id' => 'userId'])->viaTable('siteAdmin', ['siteId' => 'id']);
    }


    /** @var null|SiteSettings */
    private $settingsObject = null;

    /**
     * @return SiteSettings
     */
    public function getSettings()
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new SiteSettings($this->settings);
        }
        return $this->settingsObject;
    }

    /**
     * @param SiteSettings $settings
     */
    public function setSettings($settings)
    {
        $this->settingsObject = $settings;
        $this->settings       = $settings->toJSON();
    }


    /**
     * @return Site[]
     */
    public static function getSidebarSites()
    { // @TODO

        //$fp = fopen("/tmp/db.log", "a"); fwrite($fp, "Query\n"); fclose($fp);

        /** @var Site[] $sites */
        $sites = Site::find()->orderBy('id DESC')->all();
        /*
        $reihen2 = array();
        foreach ($reihen as $reihe) {
        if ($reihe->aktuelle_veranstaltung &&
        !$reihe->aktuelle_veranstaltung->getEinstellungen()->wartungs_modus_aktiv) {
            $reihen2[] = $reihe;
        }
        */
        return $sites;
    }

    /**
     * @param SiteCreateForm $form
     * @return Site
     * @throws DB
     */
    public static function createFromForm(SiteCreateForm $form)
    {
        $site             = new Site();
        $site->title      = $form->title;
        $site->titleShort = $form->title;
        $site->contact    = $form->title;
        $site->subdomain  = $form->subdomain;
        $site->public     = 1;

        $siteSettings               = $site->getSettings();
        $siteSettings->willingToPay = $form->isWillingToPay;
        $site->setSettings($siteSettings);

        if (!$site->save()) {
            throw new DB($site->getErrors());
        }

        return $site;
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
        // // @TODO
        //if (Yii::app()->params['admin_user_id'] !== null &&
        //$person->id == Yii::app()->params['admin_user_id']) return true;
        return false;
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
        $ich = User::findOne(["auth" => $user->id]);
        /** @var User $ich */
        if ($ich == null) {
            return false;
        }
        return $this->isAdmin($ich);
    }

    /**
     * @return SiteSpecificBehavior
     */
    public function getBehaviorClass()
    {
        /** @var AntragsgruenAppParams $params */
        $params = \Yii::$app->params;

        if (isset($params->siteBehaviorClasses[$this->id])) {
            return $params->siteBehaviorClasses[$this->id];
        }
        return new SiteSpecificBehavior();
    }
}
