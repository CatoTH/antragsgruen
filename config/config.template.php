<?php


$params                      = new \app\models\settings\AntragsgruenApp();
$params->dbConnection        = [
    'class'          => 'yii\db\Connection',
    "dsn"            => "mysql:host=localhost;dbname=###DB###",
    "emulatePrepare" => true,
    "username"       => "###USERNAME###",
    "password"       => "###PASSWORD###",
    "charset"        => "utf8mb4",
];
$params->randomSeed          = "RANDOMSEED";
$params->domainPlain         = "https://www.example.org/";
$params->domainSubdomain     = "https://<siteId:[\w_-]+>.example.org/";
$params->siteBehaviorClasses = [
    // 1 => '\app\models\siteSpecificBehavior\MyClass'
];
return $params;
