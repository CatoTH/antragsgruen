<?php

namespace unit;

use Yii;
use yii\base\InvalidConfigException;
use yii\di\Container;

class TestBase extends \Codeception\TestCase\Test
{
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->destroyApplication();
        parent::tearDown();
    }

    /**
     * Mocks up the application instance.
     * @param array $config the configuration that should be used to generate the application instance.
     * If null, [[appConfig]] will be used.
     * @return \yii\web\Application|\yii\console\Application the application instance
     * @throws InvalidConfigException if the application configuration is invalid
     */
    protected function mockApplication($config = null)
    {
        Yii::$container = new Container();

        $config = 'tests/config/acceptance.php';
        if (is_string($config)) {
            $configFile = Yii::getAlias($config);
            if (!is_file($configFile)) {
                throw new InvalidConfigException("The application configuration file does not exist: $config");
            }
            $config = require($configFile);
        }
        if (is_array($config)) {
            if (!isset($config['class'])) {
                $config['class'] = 'yii\web\Application';
            }
            return Yii::createObject($config);
        } else {
            throw new InvalidConfigException('Please provide a configuration array to mock up an application.');
        }
    }

    /**
     * Destroys the application instance created by [[mockApplication]].
     */
    protected function destroyApplication()
    {
        Yii::$app = null;
        Yii::$container = new Container();
    }
}
