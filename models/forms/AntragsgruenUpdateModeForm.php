<?php

namespace app\models\forms;

use app\models\settings\AntragsgruenApp;

class AntragsgruenUpdateModeForm extends SiteCreateForm
{
    use AntragsgruenInitConfigwriteTrait;

    public ?string $updateKey = null;

    public function __construct()
    {
        parent::__construct();
        $this->configFile = \Yii::$app->basePath . '/config/config.json';
        $config           = $this->readConfigFromFile($this->configFile);
        $this->updateKey = (isset($config->updateKey) && $config->updateKey ? $config->updateKey : null);
    }

    /**
     * @throws \Yii\base\Exception
     */
    public function activateUpdate(): string
    {
        $this->updateKey = \Yii::$app->getSecurity()->generateRandomString(10);
        $this->saveConfig();
        return $this->updateKey;
    }

    protected function setConfigValues(AntragsgruenApp $config): void
    {
        $config->updateKey = $this->updateKey;
    }
}
