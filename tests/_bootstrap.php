<?php
use Codeception\Configuration;
use Codeception\Util\Autoload;

// Debug = false: for caching test cases
defined('YII_DEBUG') or define('YII_DEBUG', false);

defined('YII_ENV') or define('YII_ENV', 'test');

defined('YII_TEST_ENTRY_URL') or define('YII_TEST_ENTRY_URL', parse_url(Configuration::config()['config']['test_entry_url'], PHP_URL_PATH));
defined('YII_TEST_ENTRY_FILE') or define('YII_TEST_ENTRY_FILE', dirname(__DIR__).'/web/index-test.php');

require_once(__DIR__.'/../vendor/yiisoft/yii2/Yii.php');

$_SERVER['SCRIPT_FILENAME'] = YII_TEST_ENTRY_FILE;
$_SERVER['SCRIPT_NAME']     = YII_TEST_ENTRY_URL;
$_SERVER['SERVER_NAME']     = parse_url(Configuration::config()['config']['test_entry_url'], PHP_URL_HOST);
$_SERVER['SERVER_PORT']     = parse_url(Configuration::config()['config']['test_entry_url'], PHP_URL_PORT) ?: '80';

Yii::setAlias('@tests', dirname(__DIR__));

Autoload::addNamespace('Unit', __DIR__.DIRECTORY_SEPARATOR.'Unit');
