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
        // @TODO
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
