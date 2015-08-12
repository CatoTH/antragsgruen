<?php


$params                       = new \app\models\settings\AntragsgruenApp();

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

$params->multisiteMode        = true;
$params->randomSeed           = 'RANDOMSEED';
$params->cookieValidationKey  = 'RANDOMSEED';
$params->domainPlain          = 'https://www.example.org/';
$params->domainSubdomain      = 'https://<subdomain:[\w_-]+>.example.org/';
$params->siteBehaviorClasses  = [
    // 1 => '\app\models\siteSpecificBehavior\MyClass'
];
$params->authClientCollection = [
    'wurzelwerk' => [
        'class' => 'app\components\WurzelwerkAuthClient',
    ]
];

// Standard path on Debian/Linux
// $params->xelatexPath = '/usr/bin/xelatex';
// $params->xdvipdfmx = '/usr/bin/xdvipdfmx';

// Standard path on OSX
// $params->xelatexPath = '/Library/TeX/texbin/xelatex';
return $params;
