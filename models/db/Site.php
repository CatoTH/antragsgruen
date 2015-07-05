<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use app\models\exceptions\DB;
use app\models\forms\SiteCreateForm;
use app\models\sitePresets\ISitePreset;
use app\models\siteSpecificBehavior\DefaultBehavior;
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
 * @property TexTemplate
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
    public function getTexTemplates()
    {
        return $this->hasMany(TexTemplate::className(), ['siteId' => 'id']);
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


    /** @var null|\app\models\settings\Site */
    private $settingsObject = null;

    /**
     * @return \app\models\settings\Site
     */
    public function getSettings()
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new \app\models\settings\Site($this->settings);
        }
        return $this->settingsObject;
    }

    /**
     * @param \app\models\settings\Site $settings
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
    {
        // @TODO

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
     * @param ISitePreset $preset
     * @return Site
     * @throws DB
     */
    public static function createFromForm(SiteCreateForm $form, ISitePreset $preset)
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

        $preset->setSiteSettings($site);

        if (!$site->save()) {
            throw new DB($site->getErrors());
        }

        return $site;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isAdmin($user)
    {
        foreach ($this->admins as $e) {
            if ($e->id == $user->id) {
                return true;
            }
        }
        /** @var AntragsgruenApp $params */
        $params = \yii::$app->params;
        return in_array($user->id, $params->adminUserIds);
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
     * @return DefaultBehavior
     */
    public function getBehaviorClass()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;

        if (isset($params->siteBehaviorClasses[$this->id])) {
            return $params->siteBehaviorClasses[$this->id];
        }
        return new DefaultBehavior();
    }
}
