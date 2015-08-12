<?php

$params                      = new \app\models\settings\AntragsgruenApp();
$params->multisiteMode       = false;
$params->siteSubdomain       = 'stdparteitag';
$params->prettyUrl           = false;
$params->randomSeed          = 'RANDOMSEED';
$params->cookieValidationKey = 'RANDOMSEED';
$params->domainPlain         = '/';
$params->domainSubdomain     = '';
$params->resourceBase        = '/antragsgruen/web/';
$params->contactEmail        = 'info@example.org';
$params->mailFromName        = 'Antragsgrün';
$params->mailFromEmail       = 'info@example.org';
$params->autoLoginDuration   = 7 * 24 * 3600;
$params->dbConnection        = [
    'class'          => 'app\components\DBConnection',
    'dsn'            => 'mysql:host=localhost;dbname=###DB###',
    'emulatePrepare' => true,
    'username'       => '###USERNAME###',
    'password'       => '###PASSWORD###',
    'charset'        => 'utf8mb4',
];
$params->mailService         = [
    'transport' => 'sendmail',
];
return $params;
