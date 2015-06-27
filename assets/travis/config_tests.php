<?php

$params                        = new \app\models\settings\AntragsgruenApp();
$params->domainPlain           = 'http://localhost:8080/index-test.php';
$params->domainSubdomain       = 'http://localhost:8080/index-test.php';
$params->prependWWWToSubdomain = false;
$params->randomSeed            = '123456';
$params->cookieValidationKey   = 'ljelkkjlj';
$params->confirmEmailAddresses = true;
$params->dbConnection          = [
    'class'          => 'yii\db\Connection',
    'dsn'            => 'mysql:host=localhost;dbname=antragsgruen_tests',
    'emulatePrepare' => true,
    'username'       => 'root',
    'password'       => '',
    'charset'        => 'utf8mb4',
];

return $params;
