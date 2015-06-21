<?php


$params                       = new \app\models\settings\AntragsgruenApp();
$params->dbConnection         = [
    'class'          => 'app\components\DBConnection',
    'dsn'            => 'mysql:host=localhost;dbname=###DB###',
    'emulatePrepare' => true,
    'username'       => '###USERNAME###',
    'password'       => '###PASSWORD###',
    'charset'        => 'utf8mb4',
];
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
return $params;
