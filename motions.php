<?php
/*
 Plugin Name: Motions
 Plugin URI: http://wordpress.org/extend/plugins/motions/
 Description: Antragsgrün
 Author: Tobias HÖßl
 Version: 0.1.0
 Author URI: https://www.hoessl.eu/
 */

use app\components\wordpress\WordpressCompatibility;

defined('ABSPATH') or die('No script kiddies please!');

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'wordpress');

define('ANTRAGSGRUEN_WP_PATH', '/motions');
define('ANTRAGSGRUEN_WP_VERSION', '0.1.1');

require(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
require(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR .
        'yiisoft' . DIRECTORY_SEPARATOR . 'yii2' . DIRECTORY_SEPARATOR . 'Yii.php');

require(__DIR__ . '/components/wordpress/Application.php');

/** @global $table_prefix */

$config = json_encode([
    "dbConnection"          => [
        "dsn"            => "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        "emulatePrepare" => true,
        "username"       => DB_USER,
        "password"       => DB_PASSWORD,
        "charset"        => DB_CHARSET
    ],
    "tablePrefix"           => $table_prefix . "motions_",
    "siteSubdomain"         => "stdparteitag",
    "prettyUrl"             => true,
    "resourceBase"          => "/wp-content/plugins/motions/web/",
    "baseLanguage"          => "de",
    "randomSeed"            => "aPzpXYt-RLrRf6YURmIQ_JKMsTAa9nmz",
    "multisiteMode"         => false,
    "domainPlain"           => "http://motionpress.localhost/motions/",
    "domainSubdomain"       => ANTRAGSGRUEN_WP_PATH . '/',
    "hasWurzelwerk"         => true,
    "createNeedsWurzelwerk" => false,
    "prependWWWToSubdomain" => true,
    "pdfLogo"               => "",
    "confirmEmailAddresses" => true,
    "mailFromName"          => "Testveranstaltung",
    "mailFromEmail"         => "",
    "adminUserIds"          => [],
    "siteBehaviorClasses"   => [],
    "behaviorClass"         => null,
    "authClientCollection"  => [],
    "blockedSubdomains"     => [],
    "autoLoginDuration"     => 31536000,
    "tmpDir"                => "/tmp/",
    "xelatexPath"           => null,
    "xdvipdfmx"             => null,
    "mailService"           => null
]);
$config = require(__DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'web.php');

$app = new app\components\wordpress\Application($config);

WordpressCompatibility::registerComponents();
WordpressCompatibility::runApp($app);
