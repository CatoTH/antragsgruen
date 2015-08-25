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
}
