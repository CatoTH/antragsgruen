<?php
namespace app\models\db;

use app\models\SiteSettings;
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
        $this->settings        = $settings->toJSON();
    }


    /**
     * @return Site[]
     */
    public static function getSidebarSites()
    { // @TODO

        //$fp = fopen("/tmp/db.log", "a"); fwrite($fp, "Query\n"); fclose($fp);

        /** @var Site[] $sites */
        $sites  = Site::find()->orderBy('id DESC')->all();
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
}
