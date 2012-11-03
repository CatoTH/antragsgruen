<?php

setlocale(LC_ALL, 'de_DE');

if (ini_get("max_execution_time") < 180) ini_set("max_execution_time", 180);

ini_set('include_path', ini_get('include_path') . ":" . dirname(__FILE__) . "/../libraries/");
require_once(dirname(__FILE__) . "/../vendor/autoload.php");

// change the following paths if necessary
$yii=dirname(__FILE__).'/../libraries/yii-1.1.12.b600af/framework/yii.php';
$config=dirname(__FILE__).'/../protected/config/main.php';

// remove the following lines when in production mode
defined('YII_DEBUG') or define('YII_DEBUG',true);
// specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

require_once($yii);

$app = Yii::createWebApplication($config);
$app->language = "de";
$app->layout = "bootstrap";
$app->getClientScript()->registerScriptFile(
    Yii::app()->request->baseUrl.'/antraege.js',
    CClientScript::POS_END
);
/** @var Bootstrap $boot  */
$boot = $app->getComponent("bootstrap");
//$boot->registerResponsiveCss();
$app->getClientScript()->registerCssFile(Yii::app()->request->baseUrl.'/css/antraege.css');
$app->getClientScript()->registerCssFile(Yii::app()->request->baseUrl.'/css/antraege-print.css', 'print');

$font_css = Yii::app()->params['font_css'];
if ($font_css != "") $app->getClientScript()->registerCssFile($font_css);

$app->run();
