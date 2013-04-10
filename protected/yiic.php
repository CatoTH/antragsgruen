<?php

if (!file_exists(dirname(__FILE__) . "/../vendor/autoload.php")) {
	die("Installation noch nicht vollst&auml;ndig: bitte f&uuml;hre 'composer install' aus. Falls composer nicht installiert ist, siehe: http://getcomposer.org/");
}
require_once(dirname(__FILE__) . "/../vendor/autoload.php");


// change the following paths if necessary
$yiic=dirname(__FILE__).'/../vendor/yiisoft/yii/framework/yiic.php';
$config=dirname(__FILE__).'/config/console.php';



require_once($yiic);
