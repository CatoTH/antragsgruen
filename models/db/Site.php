<?php

namespace app\models\db;

use app\components\Tools;
use app\models\settings\AntragsgruenApp;
use yii\db\{ActiveQuery, ActiveRecord};

/**
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
 * @property Consultation|null $currentConsultation
 * @property Consultation[] $consultations
 * @property ConsultationText[] $texts
 * @property ConsultationFile[] $files
 * @property ConsultationUserGroup[] $userGroups
 * @property TexTemplate $texTemplates
 */
class Site extends ActiveRecord
{
    public const STATUS_ACTIVE   = 0;
    public const STATUS_INACTIVE = 1;
    public const STATUS_DELETED  = -1;

    public const TITLE_SHORT_MAX_LEN = 100;

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'site';
    }

    public function getCurrentConsultation(): ActiveQuery
    {
        return $this->hasOne(Consultation::class, ['id' => 'currentConsultationId']);
    }

    public function getConsultations(): ActiveQuery
    {
        return $this->hasMany(Consultation::class, ['siteId' => 'id'])->where('consultation.dateDeletion IS NULL');
    }

    public function getUserGroups(): ActiveQuery
    {
        return $this->hasMany(ConsultationUserGroup::class, ['siteId' => 'id']);
    }

    public function getTexTemplates(): ActiveQuery
    {
        return $this->hasMany(TexTemplate::class, ['siteId' => 'id']);
    }

    public function getFiles(): ActiveQuery
    {
        return $this->hasMany(ConsultationFile::class, ['siteId' => 'id']);
    }

    /**
     * @return ConsultationFile[]
     */
    public function getFileImages(): array
    {
        $images = array_filter($this->files, function (ConsultationFile $file) {
            return (in_array($file->mimetype, ['image/png', 'image/jpeg', 'image/gif']));
        });
        return array_values($images);
    }

    public function getTexts(): ActiveQuery
    {
        return $this->hasMany(ConsultationText::class, ['siteId' => 'id']);
    }

    public function rules(): array
    {
        return [
            [['subdomain', 'title', 'public', 'dateCreation'], 'required'],
            [['title', 'titleShort', 'public', 'contact', 'organization'], 'safe'],
            [['id', 'currentConsultationId', 'public', 'status'], 'number'],
        ];
    }


    private ?\app\models\settings\Site $settingsObject = null;

    public function getSettings(): \app\models\settings\Site
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

    public function setSettings(\app\models\settings\Site $settings): void
    {
        $this->settingsObject = $settings;
        $this->settings       = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public static function isSubdomainAvailable(string $subdomain): bool
    {
        if ($subdomain == '') {
            return false;
        }
        if (in_array($subdomain, AntragsgruenApp::getInstance()->blockedSubdomains)) {
            return false;
        }
        if (!preg_match('/^[A-Za-z0-9]([A-Za-z0-9\-]{0,61}[A-Za-z0-9])?$/siu', $subdomain)) {
            return false;
        }
        $site = Site::findOne(['subdomain' => $subdomain]);
        return ($site === null);
    }

    public function getBaseUrl(): string
    {
        $domain = str_replace('<subdomain:[\w_-]+>', $this->subdomain, AntragsgruenApp::getInstance()->domainSubdomain);

        return trim($domain);
    }

    public function createDefaultSiteAdminGroup(): ConsultationUserGroup
    {
        $group = ConsultationUserGroup::createDefaultGroupSiteAdmin($this);
        $this->link('userGroups', $group);

        return $group;
    }

    public function setDeleted(): void
    {
        $this->status = self::STATUS_DELETED;
        $this->subdomain = null;
        $this->dateDeletion = date('Y-m-d H:i:s');
        $this->save(false);

        foreach ($this->consultations as $consultation) {
            $consultation->setDeleted();
        }
    }

    public function readyForPurge(): bool
    {
        if ($this->dateDeletion === null) {
            return false;
        }
        $params = AntragsgruenApp::getInstance();
        if ($params->sitePurgeAfterDays === null || $params->sitePurgeAfterDays < 1) {
            return false;
        }
        $deleted = Tools::dateSql2timestamp($this->dateDeletion);
        $days    = floor((time() - $deleted) / (3600 * 24));

        return ($days > $params->sitePurgeAfterDays);
    }
}
