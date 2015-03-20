<?php


$params                          = new \app\models\settings\AntragsgruenApp();
$params->dbConnection            = [
    'class'          => 'yii\db\Connection',
    "dsn"            => "mysql:host=localhost;dbname=###DB###",
    "emulatePrepare" => true,
    "username"       => "###USERNAME###",
    "password"       => "###PASSWORD###",
    "charset"        => "utf8mb4",
];
$params->requireEmailForAccounts = false;
$params->confirmEmailAddresses   = false;
$params->domainPlain             = "http://localhost:8080/";
$params->domainSubdomain         = "http://localhost:8080/";
$params->prependWWWToSubdomain   = false;
$params->randomSeed              = "123456";
return $params;
