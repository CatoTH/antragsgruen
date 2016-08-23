<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use app\models\siteSpecificBehavior\DefaultBehavior;
use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $currentConsultationId
 * @property int $public
 * @property string $subdomain
 * @property string $organization
 * @property string $title
 * @property string $titleShort
 * @property string $dateCreation
 * @property string $settings
 * @property string $contact
 * @property int $status
 *
 * @property Consultation $currentConsultation
 * @property Consultation[] $consultations
 * @property User[] $admins
 * @property TexTemplate
 */
class Site extends ActiveRecord
{
    const STATUS_ACTIVE   = 0;
    const STATUS_INACTIVE = 1;
    const STATUS_DELETED  = -1;

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'site';
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
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $this->hasMany(User::class, ['id' => 'userId'])
            ->viaTable($app->tablePrefix . 'siteAdmin', ['siteId' => 'id'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['subdomain', 'title', 'public', 'dateCreation'], 'required'],
            [['title', 'titleShort', 'public', 'contact', 'organization'], 'safe'],
            [['id', 'currentConsultationId', 'public', 'status'], 'number'],
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
     * @param string $subdomain
     * @return boolean
     */
    public static function isSubdomainAvailable($subdomain)
    {
        if ($subdomain == '') {
            return false;
        }
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        if (in_array($subdomain, $params->blockedSubdomains)) {
            return false;
        }
        $site = Site::findOne(['subdomain' => $subdomain]);
        return ($site === null);
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
            $shownSites[] = $site;
        }

        return $shownSites;
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

    /**
     */
    public function deleteSite()
    {
        $this->status    = static::STATUS_DELETED;
        $this->subdomain = null;
        $this->save(false);
    }
}
