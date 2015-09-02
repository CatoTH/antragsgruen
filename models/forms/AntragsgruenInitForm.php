<?php

namespace app\models\forms;

use app\models\db\Site;
use app\models\exceptions\Internal;
use yii\base\Model;

class AntragsgruenInitForm extends Model
{
    /**
     * @var string
     */
    public $siteUrl;

    public $sqlType;
    public $sqlHost;
    public $sqlUsername;
    public $sqlPassword;
    public $sqlDB;
    public $sqlFile;

    public $adminUsername;
    public $adminPassword;

    public $configFile;

    /**
     * @param string $configFile
     */
    public function __construct($configFile)
    {
        parent::__construct();
        $this->configFile = $configFile;
    }


    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['siteUrl', 'sqlType', 'adminUsername', 'adminPassword'], 'required'],
            [['sqlType', 'sqlHost', 'sqlFile', 'sqlUsername', 'sqlPassword', 'sqlDB'], 'safe'],
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
     * @return bool
     * @throws \Exception
     */
    public function verifyDBConnection()
    {
        try {
            $connConfig = $this->getDBConfig();
            $connection = new \yii\db\Connection($connConfig);
            $command    = $connection->createCommand('SELECT COUNT(*) FROM ' . Site::tableName());
            $command->execute();
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
            throw new \Exception($message);
        }
    }

    /**
     * @return string[]
     */
    public function verifyConfiguration()
    {
        $errors = [];
        try {
            $this->verifyDBConnection();
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }
        // @TODO
        return $errors;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return file_exists($this->configFile);
    }
}
