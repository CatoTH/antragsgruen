<?php
namespace Helper;

use Codeception\Exception\ModuleException;
use Codeception\TestCase;

class ConfigurationChanger extends \Codeception\Module
{
    public static $DEFAULT_CONFIGURATION = [
        'confirmEmailAddresses' => true,
        'xelatexPath'           => null,
        'xdvipdfmx'             => null,
        'mailService'           => [
            'transport' => 'sendmail',
        ]
    ];

    /**
     * @param TestCase $test
     */
    public function _before(TestCase $test)
    {
        $this->setDefaultAntragsgruenConfiguration();
    }

    /**
     */
    public function setDefaultAntragsgruenConfiguration()
    {
        $this->setAntragsgruenConfiguration(static::$DEFAULT_CONFIGURATION);
    }

    /**
     * @param array $values
     * @return \Codeception\Module\WebDriver
     * @throws \Codeception\Exception\ModuleException
     */
    public function setAntragsgruenConfiguration($values)
    {
        $configFile = \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'config' .
            DIRECTORY_SEPARATOR . 'config_tests.json';
        if (!is_writable($configFile)) {
            throw new ModuleException('ConfigurationChanger', 'Config file (' . $configFile . ') is not writable');
        }
        $config = json_decode(file_get_contents($configFile), true);
        if (!is_array($config) || count($config) == 0) {
            throw new ModuleException('ConfigurationChanger', 'Config file (' . $configFile . ') is invalid');
        }
        foreach ($values as $key => $value) {
            if (!array_key_exists($key, static::$DEFAULT_CONFIGURATION)) {
                throw new ModuleException('ConfigurationChanger', 'Invalid configuration key: ' . $key);
            }
            $config[$key] = $value;
        }
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    }
}
