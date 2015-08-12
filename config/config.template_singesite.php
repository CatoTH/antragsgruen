<?php

$params                      = new \app\models\settings\AntragsgruenApp();

/* Database Configuration */

$params->dbConnection         = [
    'class'          => 'app\components\DBConnection',
    'dsn'            => 'mysql:host=localhost;dbname=###DB###',
    'emulatePrepare' => true,
    'username'       => '###USERNAME###',
    'password'       => '###PASSWORD###',
    'charset'        => 'utf8mb4',
];


/* E-Mail Configuration */

$params->mailService          = [
    'transport' => 'sendmail',
];
/*
Alternative for sending e-mails using SMTP:
$params->mailService         = [
    'transport' => 'smtp',
    'host'      => 'servername.de',
    'authType'  => 'plain_tls', // or: plain, login, crammd5, none
    'port'      => 587,
    'username'  => 'myusername',
    'password'  => 'mypassword',
];

Alternative for sending e-mails using Mandrill:
$params->mailService           = [
    'transport' => 'mandrill',
    'apiKey'    => 'myapikey',
];
*/


/* Other Configuration */

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

return $params;
