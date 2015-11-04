<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use app\models\exceptions\DB;
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
 * @property string $dateCreation
 * @property string $settings
 * @property string $contact
 *
 * @property Consultation $currentConsultation
 * @property Consultation[] $consultations
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
        return $this->hasOne(Consultation::class, ['id' => 'currentConsultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultations()
    {
        return $this->hasMany(Consultation::class, ['siteId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTexTemplates()
    {
        return $this->hasMany(TexTemplate::class, ['siteId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdmins()
    {
        return $this->hasMany(User::class, ['id' => 'userId'])->viaTable('siteAdmin', ['siteId' => 'id'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['subdomain', 'title', 'public', 'dateCreation'], 'required'],
            [['title', 'titleShort', 'public', 'contact'], 'safe'],
            [['id', 'currentConsultationId', 'public'], 'number'],
        ];
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
        $shownSites = [];
        /** @var Site[] $sites */
        $sites = Site::find()->orderBy('dateCreation DESC')->all();
        foreach ($sites as $site) {
            if (!$site->public) {
                continue;
            }
            if (!$site->currentConsultation) {
                continue;
            }
            if ($site->currentConsultation->getSettings()->maintainanceMode) {
                continue;
            }
            $shownSites[] = $site;
        }

        return $shownSites;
    }

    /**
     * @param ISitePreset $preset
     * @param string $subdomain
     * @param string $title
     * @param string $contact
     * @param int $isWillingToPay
     * @return Site
     * @throws DB
     */
    public static function createFromForm(ISitePreset $preset, $subdomain, $title, $contact, $isWillingToPay)
    {
        $site               = new Site();
        $site->title        = $title;
        $site->titleShort   = $title;
        $site->contact      = $contact;
        $site->subdomain    = $subdomain;
        $site->public       = 1;
        $site->dateCreation = date('Y-m-d H:i:s');

        $siteSettings                          = $site->getSettings();
        $siteSettings->willingToPay            = $isWillingToPay;
        $siteSettings->willingToPayLastAskedTs = time();
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
        $myUser = User::find()->where(['auth' => $user->id])->andWhere('status != ' . User::STATUS_DELETED)->one();
        /** @var User $myUser */
        if ($myUser == null) {
            return false;
        }
        return $this->isAdmin($myUser);
    }

    /**
     * @return DefaultBehavior
     */
    public function getBehaviorClass()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;

        if (isset($params->siteBehaviorClasses[$this->id])) {
            return new $params->siteBehaviorClasses[$this->id];
        }
        return new DefaultBehavior();
    }
}
