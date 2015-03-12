<?php
define("ANTRAGSGRUEN_CALL_MODE", "shell");
$config = require(dirname(__FILE__).DIRECTORY_SEPARATOR."main.php");
unset($config["onBeginRequest"]);
unseT($config["onEndRequest"]);
return $config;
