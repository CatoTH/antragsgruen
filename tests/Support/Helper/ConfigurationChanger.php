<?php

namespace Tests\Support\Helper;

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\Module\WebDriver;
use Codeception\TestInterface;
use Yii;

class ConfigurationChanger extends Module
{
    private const DEFAULT_CONFIGURATION = [
        'confirmEmailAddresses' => true,
        'xelatexPath'           => null,
        'xdvipdfmx'             => null,
        'mailService'           => [
            'transport' => 'sendmail',
        ]
    ];

    /**
     * @param TestInterface $test
     */
    public function _before(TestInterface $test): void
    {
        $this->setDefaultAntragsgruenConfiguration();
    }

    public function setDefaultAntragsgruenConfiguration(): void
    {
        $this->setAntragsgruenConfiguration(self::DEFAULT_CONFIGURATION);
    }

    /**
     * @param array $values
     * @throws \Codeception\Exception\ModuleException
     */
    public function setAntragsgruenConfiguration(array $values): void
    {
        $configFile = Yii::$app->basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config_tests.json';
        if (!is_writable($configFile)) {
            throw new ModuleException('ConfigurationChanger', 'Config file (' . $configFile . ') is not writable');
        }
        $config = json_decode(file_get_contents($configFile), true);
        if (!is_array($config) || count($config) === 0) {
            throw new ModuleException('ConfigurationChanger', 'Config file (' . $configFile . ') is invalid');
        }
        foreach ($values as $key => $value) {
            if (!array_key_exists($key, self::DEFAULT_CONFIGURATION)) {
                throw new ModuleException('ConfigurationChanger', 'Invalid configuration key: ' . $key);
            }
            $config[$key] = $value;
        }
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    }
}
