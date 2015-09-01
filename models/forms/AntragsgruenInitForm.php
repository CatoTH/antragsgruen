<?php

namespace app\models\forms;

use yii\base\Model;

class AntragsgruenInitForm extends Model
{
    /**
     * @var string
     */
    public $siteUrl;

    public $sqlType;
    public $sqlHostFile;
    public $sqlUsername;
    public $sqlPassword;
    public $sqlDB;

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
            [['sqlType', 'sqlHostFile', 'adminUsername', 'adminPassword'], 'required'],
            [['sqlType', 'sqlHostFile', 'sqlUsername', 'sqlPassword', 'sqlDB'], 'safe'],
            [['adminUsername', 'adminPassword'], 'safe'],
        ];
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function verifyDBConnection()
    {
        try {
            $command = \yii::$app->db->createCommand('SELECT 1');
            $command->execute();
        } catch (\yii\db\Exception $e) {
            switch ($e->getCode()) {
                case 1044:
                    $message = 'The database login is correct, however I could not connect to the actual database';
                    break;
                case 1045:
                    $message = 'Invalid database username or password';
                    break;
                case 2002:
                    if (mb_strpos($e->getMessage(), 'Connection refused')) {
                        $message = 'Database: Connection refused';
                    } elseif (mb_strpos($e->getMessage(), 'getaddrinfo failed')) {
                        $message = 'Database hostname not found';
                    } else {
                        $message = 'Could not connect to database: ' . $e->getMessage();
                    }
                    break;
                default:
                    $message = 'Unknown error when trying to connect to database: ' . $e->getMessage();
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
