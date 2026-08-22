<?php
namespace Tests\Support\Helper;

use Codeception\Test\Unit;
use Yii;
use yii\base\InvalidConfigException;
use yii\di\Container;
use yii\web\Application;

class TestBase extends Unit
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
     * @param array|null $config the configuration that should be used to generate the application instance.
     *                      If null, [[appConfig]] will be used.
     * @return object|\yii\console\Application|\yii\web\Application
     * @throws InvalidConfigException if the application configuration is invalid
     */
    protected function mockApplication(?array $config = null): Application|\yii\console\Application
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
                $config['class'] = Application::class;
            }
            return Yii::createObject($config);
        }

        throw new InvalidConfigException('Please provide a configuration array to mock up an application.');
    }

    /**
     * Destroys the application instance created by [[mockApplication]].
     */
    protected function destroyApplication(): void
    {
        Yii::$app       = null;
        Yii::$container = new Container();
    }
}
