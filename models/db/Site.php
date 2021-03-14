<?php

namespace app\models\db;

use app\components\Tools;
use app\models\settings\AntragsgruenApp;
use app\models\siteSpecificBehavior\DefaultBehavior;
use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $currentConsultationId
 * @property int $public
 * @property string|null $subdomain
 * @property string $organization
 * @property string $title
 * @property string $titleShort
 * @property string $dateCreation
 * @property string|null $dateDeletion
 * @property string $settings
 * @property string $contact
 * @property int $status
 *
 * @property Consultation $currentConsultation
 * @property Consultation[] $consultations
 * @property ConsultationText[] $texts
 * @property ConsultationFile[] $files
 * @property User[] $admins
 * @property TexTemplate $texTemplates
 */
class Site extends ActiveRecord
{
    const STATUS_ACTIVE   = 0;
    const STATUS_INACTIVE = 1;
    const STATUS_DELETED  = -1;

    const TITLE_SHORT_MAX_LEN = 100;

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var AntragsgruenApp $app */
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
        return $this->hasMany(Consultation::class, ['siteId' => 'id'])->where('consultation.dateDeletion IS NULL');
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
    public function getFiles()
    {
        return $this->hasMany(ConsultationFile::class, ['siteId' => 'id']);
    }

    /**
     * @return ConsultationFile[]
     */
    public function getFileImages()
    {
        $images = array_filter($this->files, function (ConsultationFile $file) {
            return (in_array($file->mimetype, ['image/png', 'image/jpeg', 'image/gif']));
        });
        return array_values($images);
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
     * @return \yii\db\ActiveQuery
     */
    public function getTexts()
    {
        return $this->hasMany(ConsultationText::class, ['siteId' => 'id']);
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
            $settingsClass = \app\models\settings\Site::class;

            foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
                if ($pluginClass::getSiteSettingsClass($this)) {
                    $settingsClass = $pluginClass::getSiteSettingsClass($this);
                }
            }

            $this->settingsObject = new $settingsClass($this->settings);
        }
        return $this->settingsObject;
    }

    /**
     * @param \app\models\settings\Site $settings
     */
    public function setSettings($settings)
    {
        $this->settingsObject = $settings;
        $this->settings       = json_encode($settings, JSON_PRETTY_PRINT);
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

    public function getBaseUrl(): string
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        $domain = str_replace('<subdomain:[\w_-]+>', $this->subdomain, $params->domainSubdomain);

        return trim(explode("//", $domain)[1], '/');
    }

    /**
     * @return Site[]
     */
    public static function getSidebarSites()
    {
        if (AntragsgruenApp::getInstance()->mode == 'sandbox') {
            return [];
        }
        $shownSites = [];
        /** @var Site[] $sites */
        $sites = Site::find()->with('currentConsultation')->all();
        foreach ($sites as $site) {
            if (!$site->public) {
                continue;
            }
            if (!$site->currentConsultation) {
                continue;
            }
            $shownSites[] = $site;
        }

        usort($shownSites, function (Site $site1, Site $site2) {
            $date1 = $site1->currentConsultation->dateCreation;
            $date2 = $site2->currentConsultation->dateCreation;
            return -1 * Tools::compareSqlTimes($date1, $date2);
        });

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
        $params = \Yii::$app->params;
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
        foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
            $behavior = $pluginClass::getSiteSpecificBehavior($this);
            if ($behavior) {
                return new $behavior();
            }
        }

        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        if (isset($params->siteBehaviorClasses[$this->id])) {
            return new $params->siteBehaviorClasses[$this->id]();
        }

        return new DefaultBehavior();
    }

    /**
     */
    public function setDeleted()
    {
        $this->status       = static::STATUS_DELETED;
        $this->subdomain    = null;
        $this->dateDeletion = date('Y-m-d H:i:s');
        $this->save(false);

        foreach ($this->consultations as $consultation) {
            $consultation->setDeleted();
        }
    }

    /**
     * @return bool
     */
    public function readyForPurge()
    {
        if ($this->dateDeletion === null) {
            return false;
        }
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        if ($params->sitePurgeAfterDays === null || $params->sitePurgeAfterDays < 1) {
            return false;
        }
        $deleted = Tools::dateSql2timestamp($this->dateDeletion);
        $days    = floor((time() - $deleted) / (3600 * 24));

        return ($days > $params->sitePurgeAfterDays);
    }
}
