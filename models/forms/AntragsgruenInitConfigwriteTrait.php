<?php

namespace app\models\forms;

use app\models\settings\AntragsgruenApp;

/**
 * Class AntragsgruenInitConfigwriteTrait
 * @package app\models\forms
 *
 * @method protected setConfigValues(AntragsgruenApp $config)
 */
trait AntragsgruenInitConfigwriteTrait
{
    protected $configFile = null;

    public function readConfigFromFile($configFile = null)
    {
        if ($configFile) {
            $this->configFile = $configFile;
        }

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

        return $config;
    }


    /**
     */
    public function saveConfig()
    {
        $config = $this->readConfigFromFile();
        $this->setConfigValues($config);
        $file = fopen($this->configFile, 'w');
        fwrite($file, $config->toJSON());
        fclose($file);
    }
}
