<?php


$params                        = new \app\models\settings\AntragsgruenApp();
$params->domainPlain           = "http://localhost:8080/";
$params->domainSubdomain       = "http://localhost:8080/";
$params->prependWWWToSubdomain = false;
$params->randomSeed            = "123456";
return $params;
