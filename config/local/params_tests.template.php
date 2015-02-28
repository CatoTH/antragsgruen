<?php


$params                        = new \app\models\settings\AntragsgruenApp();
$params->domainPlain           = "https://www.example.org/";
$params->domainSubdomain       = "https://<siteId:[\w_-]+>.example.org/";
$params->prependWWWToSubdomain = false;
$params->randomSeed            = "123456";
return $params;
