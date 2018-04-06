<?php

namespace app\models\forms;

use app\models\settings\AntragsgruenApp;

class AntragsgruenUpdateModeForm extends SiteCreateForm
{
    use AntragsgruenInitConfigwriteTrait;

    public $update_key = null;

    /**
     */
    public function __construct()
    {
        parent::__construct();
        $this->configFile = \Yii::$app->basePath . '/config/config.json';
        $config           = $this->readConfigFromFile($this->configFile);
        $this->update_key = (isset($config->update_key) && $config->update_key ? $config->update_key : null);
    }

    /**
     * @throws \yii\base\Exception
     * @return string
     */
    public function activateUpdate()
    {
        $this->update_key = \Yii::$app->getSecurity()->generateRandomString(10);
        $this->saveConfig();
        return $this->update_key;
    }

    /**
     * @param AntragsgruenApp $config
     */
    protected function setConfigValues(AntragsgruenApp $config)
    {
        $config->update_key = $this->update_key;
    }
}
