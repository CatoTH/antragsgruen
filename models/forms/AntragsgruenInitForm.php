<?php

namespace app\models\forms;

use app\models\db\Consultation;
use app\models\db\Site;
use app\models\settings\Site as SiteSettings;
use app\models\db\User;
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use app\models\sitePresets\SitePresets;
use yii\base\Model;
use yii\db\Connection;

class AntragsgruenInitForm extends Model
{
    /** @var string */
    public $configFile;

    public $siteUrl;
    public $siteTitle;
    public $siteSubdomain = 'std';
    public $siteEmail;
    public $sitePreset;

    public $sqlType = 'mysql';
    public $sqlHost;
    public $sqlUsername;
    public $sqlPassword;
    public $sqlDB;
    public $sqlFile;

    public $adminUsername;
    public $adminPassword;

    /** @var int[] */
    public $adminIds;
    /** @var User */
    public $adminUser;

    /** @var boolean */
    public $sqlCreateTables = true;
    public $prettyUrls      = true;

    /**
     * @param string $configFile
     */
    public function __construct($configFile)
    {
        parent::__construct();
        $this->configFile = $configFile;

        if (file_exists($configFile)) {
            $configJson = file_get_contents($configFile);
            try {
                $config           = new AntragsgruenApp($configJson);
                $this->siteUrl    = trim($config->domainPlain, '/') . $config->resourceBase;
                $this->prettyUrls = $config->prettyUrl;
                $this->siteEmail  = $config->mailFromEmail;
                $this->setDatabaseFromParams($config->dbConnection);
                $this->adminIds = $config->adminUserIds;

                if ($config->siteSubdomain) {
                    $this->siteSubdomain = $config->siteSubdomain;
                }

                if ($this->verifyDBConnection(false)) {
                    $site = $this->getDefaultSite();
                    if ($site) {
                        $this->siteTitle = $site['title'];
                    }
                    $adminUser = $this->getAdminUser();
                    if ($adminUser) {
                        $this->adminUser = $adminUser;
                    }
                }
            } catch (\Exception $e) {
            }
        } else {
            $config           = new AntragsgruenApp('');
            $this->siteUrl    = trim($config->domainPlain, '/') . $config->resourceBase;
            $this->prettyUrls = $config->prettyUrl;
        }
    }

    /**
     * @param array $params
     */
    private function setDatabaseFromParams($params)
    {
        if (!is_array($params) || !isset($params['dsn'])) {
            return;
        }
        if (isset($params['username'])) {
            $this->sqlUsername = $params['username'];
        }
        if (isset($params['password'])) {
            $this->sqlPassword = $params['password'];
        }

        $parts = explode(':', $params['dsn']);
        if (count($parts) != 2) {
            return;
        }
        $this->sqlType = $parts[0];
        $params        = explode(';', $parts[1]);
        for ($i = 0; $i < count($params); $i++) {
            $parts = explode('=', $params[$i]);
            if (count($parts) == 2) {
                if ($parts[0] == 'dbname') {
                    $this->sqlDB = $parts[1];
                }
                if ($parts[0] == 'host') {
                    $this->sqlHost = $parts[1];
                }
            }
        }
    }


    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['siteUrl', 'siteTitle', 'siteEmail', 'sqlType', 'adminUsername', 'adminPassword'], 'required'],
            [['sitePreset'], 'number'],
            [['sqlType', 'sqlHost', 'sqlFile', 'sqlUsername', 'sqlDB', 'sqlCreateTables'], 'safe'],
            [['siteUrl', 'siteTitle', 'sitePreset', 'adminUsername', 'adminPassword'], 'safe'],
        ];
    }

    /**
     * @return array
     * @throws Internal
     */
    public function getDBConfig()
    {
        if ($this->sqlType == 'mysql') {
            return [
                'dsn'            => 'mysql:host=' . $this->sqlHost . ';dbname=' . $this->sqlDB,
                'emulatePrepare' => true,
                'username'       => $this->sqlUsername,
                'password'       => $this->sqlPassword,
                'charset'        => 'utf8mb4',
            ];
        }
        throw new Internal('Unknown SQL Type');
    }

    /**
     * @param bool $exceptions
     * @return bool
     * @throws Internal
     * @throws \Exception
     */
    public function verifyDBConnection($exceptions = true)
    {
        try {
            $connConfig = $this->getDBConfig();
            $connection = new Connection($connConfig);
            $connection->createCommand('SHOW TABLES')->queryAll();
            return true;
        } catch (\yii\db\Exception $e) {
            switch ($e->getCode()) {
                case 1044:
                    $message = 'The database login is correct, however I could not connect to the actual database.
                    Maybe a permission problem?';
                    break;
                case 1045:
                    $message = 'Invalid database username or password';
                    break;
                case 1046:
                    $message = 'Invalid database name entered';
                    break;
                case 2002:
                    if (mb_stripos($e->getMessage(), 'Connection refused')) {
                        $message = 'Database: Connection refused';
                    } elseif (mb_stripos($e->getMessage(), 'getaddrinfo failed')) {
                        $message = 'Database hostname not found';
                    } else {
                        $message = 'Could not connect to database: ' . $e->getMessage();
                    }
                    break;
                default:
                    if (mb_stripos($e->getMessage(), 'No database selected') !== false) {
                        $message = 'Invalid or no database name entered';
                    } else {
                        $message = 'Unknown error when trying to connect to database: ' . $e->getMessage();
                    }
            }
            if ($exceptions) {
                throw new \Exception($message);
            } else {
                return false;
            }
        }
    }

    /**
     * @return string[]
     */
    public function verifyConfiguration()
    {
        $errors = [];
        try {
            $this->verifyDBConnection(true);
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }
        // @TODO
        return $errors;
    }

    /**
     * @return Site|null
     */
    public function getDefaultSite()
    {
        if (!$this->verifyDBConnection(false) || !$this->tablesAreCreated()) {
            return null;
        }
        $sites = Site::find()->all();
        if (count($sites) > 0) {
            return $sites[0];
        } else {
            return null;
        }
    }

    /**
     * @return boolean
     */
    public function hasDefaultData()
    {
        $site = $this->getDefaultSite();
        if (!$site || !$site->currentConsultation) {
            return false;
        }
        return (count($site->currentConsultation->motionTypes) > 0);
    }

    /**
     * @return null|User
     */
    public function getAdminUser()
    {
        if (count($this->adminIds) == 0) {
            return null;
        }
        return User::findOne($this->adminIds[0]);
    }

    /**
     * @return Site
     * @throws \app\models\exceptions\DB
     * @throws \app\models\exceptions\Internal
     */
    public function createSite()
    {
        $preset = SitePresets::getPreset($this->sitePreset);
        if (!$this->adminUser) {
            throw new \app\models\exceptions\Internal('Admin user not created');
        }

        $site         = Site::createFromForm(
            $preset,
            $this->siteSubdomain,
            $this->siteTitle,
            '',
            '',
            SiteSettings::PAYS_NOT,
            Site::STATUS_ACTIVE
        );
        $consultation = Consultation::createFromForm(
            $site,
            $this->adminUser,
            $preset,
            $this->sitePreset,
            $this->siteTitle,
            $this->siteSubdomain,
            1
        );
        $site->link('currentConsultation', $consultation);
        $site->link('admins', $this->adminUser);

        $preset->createMotionTypes($consultation);
        $preset->createMotionSections($consultation);
        $preset->createAgenda($consultation);

        return $site;
    }

    /**
     */
    public function updateSite()
    {
        $site             = $this->getDefaultSite();
        $site->title      = $this->siteTitle;
        $site->titleShort = $this->siteTitle;
        $site->save();

        foreach ($site->consultations as $consultation) {
            $consultation->title      = $this->siteTitle;
            $consultation->titleShort = $this->siteTitle;
            $consultation->save();
        }
    }

    /**
     * @return bool
     */
    public function hasAdminAccount()
    {
        return (count($this->adminIds) > 0);
    }

    /**
     */
    public function createOrUpdateAdminAccount()
    {
        /** @var User|null $user */
        $user = User::findOne(['auth' => 'email:' . $this->adminUsername]);
        if ($user) {
            $user->pwdEnc = password_hash($this->adminPassword, PASSWORD_DEFAULT);
            if (!$user->save()) {
                var_dump($user->getErrors());
                die();
            }
            $this->adminIds[] = $user->id;
            $this->adminUser  = $user;
        } else {
            $this->createAdminAccount();
        }
    }

    /**
     */
    public function createAdminAccount()
    {
        $user                 = new User();
        $user->auth           = 'email:' . $this->adminUsername;
        $user->status         = User::STATUS_CONFIRMED;
        $user->email          = $this->adminUsername;
        $user->emailConfirmed = 1;
        $user->pwdEnc         = password_hash($this->adminPassword, PASSWORD_DEFAULT);
        $user->name           = '';
        if (!$user->save()) {
            var_dump($user->getErrors());
            die();
        }
        $this->adminIds[] = $user->id;
        $this->adminUser  = $user;
    }

    /**
     * @return bool
     */
    public function tablesAreCreated()
    {
        try {
            $connConfig = $this->getDBConfig();
            $connection = new Connection($connConfig);
            $tables     = $connection->createCommand('SHOW TABLES')->queryAll();
            $found      = false;
            foreach ($tables as $table) {
                if (in_array('site', $table)) {
                    $found = true;
                }
            }
            return $found;
        } catch (\yii\db\Exception $e) {
            return false;
        }
    }

    /**
     */
    public function createTables()
    {
        $connConfig = $this->getDBConfig();
        $connection = new Connection($connConfig);

        $createString = file_get_contents(
            \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
            'db' . DIRECTORY_SEPARATOR . 'create.sql'
        );
        $command      = $connection->createCommand($createString);
        $command->execute();

        $createString = file_get_contents(
            \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
            'db' . DIRECTORY_SEPARATOR . 'data.sql'
        );
        $command      = $connection->createCommand($createString);
        $command->execute();
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return file_exists($this->configFile) && $this->tablesAreCreated();
    }

    /**
     * @return AntragsgruenApp
     */
    public function getConfig()
    {
        if (file_exists($this->configFile)) {
            $configJson = file_get_contents($this->configFile);
            try {
                $config = new AntragsgruenApp($configJson);
            } catch (\Exception $e) {
                $config = new AntragsgruenApp('');
            }
        } else {
            $config = new AntragsgruenApp('');
        }

        if ($config->randomSeed === null || $config->randomSeed == '') {
            $config->randomSeed = \Yii::$app->getSecurity()->generateRandomString();
        }

        $config->domainPlain   = $this->siteUrl;
        $config->prettyUrl     = $this->prettyUrls;
        $config->mailFromEmail = $this->siteEmail;
        $config->mailFromName  = $this->siteTitle;
        $config->dbConnection  = $this->getDBConfig();
        $config->siteSubdomain = $this->siteSubdomain;

        try {
            $defaultSite = $this->getDefaultSite();
            if ($defaultSite) {
                $config->siteSubdomain = $defaultSite->subdomain;
            }
        } catch (\Exception $e) {
        }

        if ($this->adminUser && !in_array($this->adminUser->id, $config->adminUserIds)) {
            $config->adminUserIds[] = $this->adminUser->id;
        }

        return $config;
    }

    /**
     */
    public function saveConfig()
    {
        $file = fopen($this->configFile, 'w');
        fwrite($file, $this->getConfig()->toJSON());
        fclose($file);
    }
}
