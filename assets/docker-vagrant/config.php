<?php

$params                      = new \app\models\settings\AntragsgruenApp();
$params->dbConnection        = [
    'class'          => 'yii\db\Connection',
    'dsn'            => 'mysql:host=localhost;dbname=antragsgruen',
    'emulatePrepare' => true,
    'username'       => 'root',
    'password'       => 'pw',
    'charset'        => 'utf8mb4',
];
$params->randomSeed          = 'fgfdgdfgdfg';
$params->domainPlain         = 'http://localhost/';
$params->domainSubdomain     = 'http://<siteId:[\w_-]+>.localhost/';
$params->siteBehaviorClasses = [];
return $params;
