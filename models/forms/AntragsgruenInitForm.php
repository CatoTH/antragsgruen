<?php

namespace app\models\forms;

use app\models\db\User;
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use yii\base\Model;

class AntragsgruenInitForm extends Model
{
    /** @var string */
    public $configFile;

    public $siteUrl;

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
                $this->setDatabaseFromParams($config->dbConnection);
                $this->adminIds = $config->adminUserIds;
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
            [['siteUrl', 'sqlType', 'adminUsername', 'adminPassword'], 'required'],
            [['sqlType', 'sqlHost', 'sqlFile', 'sqlUsername', 'sqlPassword', 'sqlDB', 'sqlCreateTables'], 'safe'],
            [['siteUrl'], 'safe'],
            [['adminUsername', 'adminPassword'], 'safe'],
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
            $connection = new \yii\db\Connection($connConfig);
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
     * @return bool
     */
    public function hasAdminAccount()
    {
        return (count($this->adminIds) > 0);
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
        $user->save();
    }

    /**
     * @return bool
     */
    public function tablesAreCreated()
    {
        try {
            $connConfig = $this->getDBConfig();
            $connection = new \yii\db\Connection($connConfig);
            $tables     = $connection->createCommand('SHOW TABLES')->queryAll();
            return (count($tables) > 0);
        } catch (\yii\db\Exception $e) {
            return false;
        }
    }

    /**
     */
    public function createTables()
    {
        $connConfig = $this->getDBConfig();
        $connection = new \yii\db\Connection($connConfig);

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
        return file_exists($this->configFile);
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

        $config->domainPlain  = $this->siteUrl;
        $config->prettyUrl    = $this->prettyUrls;
        $config->dbConnection = $this->getDBConfig();

        return $config;
    }
}
