<?php

namespace app\models\forms;

use app\models\settings\AntragsgruenApp;

class AntragsgruenUpdateModeForm extends SiteCreateForm
{
    use AntragsgruenInitConfigwriteTrait;

    public $updateKey = null;

    /**
     */
    public function __construct()
    {
        parent::__construct();
        $this->configFile = \Yii::$app->basePath . '/config/config.json';
        $config           = $this->readConfigFromFile($this->configFile);
        $this->updateKey = (isset($config->updateKey) && $config->updateKey ? $config->updateKey : null);
    }

    /**
     * @throws \yii\base\Exception
     * @return string
     */
    public function activateUpdate()
    {
        $this->updateKey = \Yii::$app->getSecurity()->generateRandomString(10);
        $this->saveConfig();
        return $this->updateKey;
    }

    /**
     * @param AntragsgruenApp $config
     */
    protected function setConfigValues(AntragsgruenApp $config)
    {
        $config->updateKey = $this->updateKey;
    }
}
