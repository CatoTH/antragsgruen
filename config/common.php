<?php

use app\models\settings\AntragsgruenApp;

/**
 * @var AntragsgruenApp $params
 */


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'defines.php');

if (!defined('K_PATH_FONTS')) {
    // Needs to be set before the TCPDF classes are loaded, as its autoconfig would resolve a non-existing path
    define('K_PATH_FONTS', dirname(__DIR__) . '/vendor/tecnickcom/tc-lib-pdf-font/target/fonts/');
}

if (ini_get('date.timezone') == '') {
    $timezone = 'Europe/Berlin';
    date_default_timezone_set($timezone);
    ini_set('date.timezone', $timezone);
}
ini_set('tidy.clean_output', false);
ini_set('default_charset', 'UTF-8');



if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'INSTALLING')) {
    $domp         = trim($params->domainPlain, '/');
    $urls         = [
        $domp . '/<_a:(index|db-test)>' => 'installation/<_a>',
    ];
    $defaultRoute = 'installation/index';
    define('INSTALLING_MODE', true);
} else {
    // Kind of a poor man's auto-loading, as Yii's auto-loading-mechanism is not active yet.
    foreach ($params->getPluginNames() as $pluginName) {
        require_once(__DIR__ . '/../plugins/ModuleBase.php');
        $filename = __DIR__ . '/../plugins/' . $pluginName . '/Module.php';
        if (file_exists($filename)) {
            require_once($filename);
        } else {
            die("plugin file of " . $pluginName . " does not exist");
        }
    }

    $urls         = require(__DIR__ . DIRECTORY_SEPARATOR . 'urls.php');
    $defaultRoute = 'consultation/index';
    foreach ($params->getPluginClasses() as $pluginClass) {
        if ($pluginClass::getDefaultRouteOverride()) {
            $defaultRoute = $pluginClass::getDefaultRouteOverride();
        }
    }
}

if (defined('INSTALLING_MODE') || YII_ENV == 'test') {
    $params->dbConnection['class'] = 'yii\db\Connection';
} else {
    $params->dbConnection['class'] = 'app\components\yii\DBConnection';
}

$logTargets = [
    [
        'class'  => \app\components\yii\LoggerFileTarget::class,
        'levels' => ['error', 'warning'],
        'except' => [
            'yii\web\HttpException:404',
            'yii\web\HttpException:400',
        ],
    ],
];
if (class_exists(\OpenTelemetry\API\Globals::class, true) && getenv('OTEL_PHP_AUTOLOAD_ENABLED') === 'true') {
    $logTargets[] = [
        'class' => \app\components\OpenTelemetryLogTarget::class,
        'levels' => ['error', 'warning', 'info'],
    ];
}

$components = [
    'cache'        => [
        'class' => ($params->redis ? 'yii\redis\Cache' : 'yii\caching\FileCache'),
    ],
    'assetManager' => [
        'appendTimestamp' => true,
    ],
    'mailer'       => [
        'class' => 'yii\swiftmailer\Mailer',
    ],
    'log'          => [
        'traceLevel' => YII_DEBUG ? 3 : 0,
        'targets'    => $logTargets,
    ],
    'db'           => $params->dbConnection,
    'urlManager'   => [
        'class'           => 'app\components\yii\UrlManager',
        'showScriptName'  => false,
        'enablePrettyUrl' => ($params->prettyUrl && !(isset($_POST['disablePrettyUrl']) && $_POST['disablePrettyUrl'])),
        'rules'           => $urls
    ],
    'i18n'         => [
        'translations' => [
            '*' => [
                'class'    => 'app\components\yii\MessageSource',
                'basePath' => '@app/messages',
            ],
        ],
    ],
];

if (!str_starts_with($params->resourceBase, '/')) {
    require_once(__DIR__ . '/../components/StaticResourceTools.php');
    $jsDependencies = \app\components\StaticResourceTools::getJsDependencies();
    if ($jsDependencies['cdn_tag']) {
        $components['assetManager']['baseUrl'] = str_replace('{CDN_TAG}', $jsDependencies['cdn_tag'], $params->resourceBase);
        $components['assetManager']['hashCallback'] = function ($path) {
            $pathParts = explode('plugins/', $path);
            $pathParts = explode('/', $pathParts[1]);

            // We need two levels of paths, so relative links in CSS files like ../../fonts work both in local and CDN deployments
            return "plugins/" . $pathParts[0];
        };
    }
}

if ($params->redis) {
    $components['redis']   = array_merge(['class' => 'yii\redis\Connection'], $params->redis);
    $components['session'] = ['class' => 'yii\redis\Session'];
}

$bootstrap = ['log'];
$modules = [];
foreach ($params->getPluginNames() as $pluginName) {
    $modules[$pluginName] = [
        'class' => '\\app\\plugins\\' . $pluginName . '\\Module',
    ];
    $bootstrap[] = $pluginName;
}

return [
    'name'         => 'Antragsgrün',
    'bootstrap'    => $bootstrap,
    'basePath'     => dirname(__DIR__),
    'components'   => $components,
    'defaultRoute' => $defaultRoute,
    'params'       => $params,
    'language'     => $params->baseLanguage,
    'modules'      => $modules,
];
