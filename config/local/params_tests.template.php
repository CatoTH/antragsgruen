<?php


$params = new \app\models\AntragsgruenAppParams();
$params->domainPlain = "https://www.example.org/";
$params->domainSubdomain = "https://<siteId:[\w_-]+>.example.org/";
return $params;
