<?php

mb_internal_encoding("UTF-8");

Yii::setPathOfAlias('bootstrap', dirname(__FILE__) . '/../../vendor/chris83/yii-bootstrap');

define("SEED_KEY", "randomkey");
define("MULTISITE_MODE", false);
define("IGNORE_PASSWORD_MODE", false);
//define("MANDRILL_API_KEY", "");

if (MULTISITE_MODE) {
	$dom_plain = "http://antraege-v2.hoessl.eu/";
	$dom       = "http://<veranstaltungsreihe_id:[\w_-]+>.antraege-v2.hoessl.eu/";
	$domv      = $dom . "<veranstaltung_id:[\w_-]+>/";
} else {
	$dom_plain = "";
	$dom       = "";
	$domv      = $dom . "<veranstaltung_id:[\w\._-]+>/";
}

require(dirname(__FILE__) . "/common.php");
if (file_exists(dirname(__FILE__) . "/veranstaltungsspezifisch.local.php")) require(dirname(__FILE__) . "/veranstaltungsspezifisch.local.php");
require(dirname(__FILE__) . "/veranstaltungsspezifisch.std.php");


return array(
	'basePath'       => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
	'name'           => 'Grüne Anträge',

	'preload'        => array(
		'log',
	),

	'import'         => array(
		'application.models.*',
		'application.models.forms.*',
		'application.models.interfaces.*',
		'application.components.*',
		'application.controllers.*',
		'ext.giix-components.*',
	),

	'onBeginRequest' => create_function('$event', 'return ob_start("ob_gzhandler");'),
	'onEndRequest'   => create_function('$event', 'return ob_end_flush();'),

	'modules'        => array(),

	// application components
	'components'     => array(
		'user'           => array(
			// enable cookie-based authentication
			'allowAutoLogin' => true,
			'loginUrl'       => array('veranstaltung/login'),
		),
		'cache'          => array(
			'class' => 'system.caching.CFileCache',
		),
		'urlManager'     => array(
			'urlFormat'      => 'path',
			'showScriptName' => false,
			'rules'          => $url_rules
		),
		'authManager'    => array(
			'class'        => 'CDbAuthManager',
			'connectionID' => 'db',
		),

		'db'             => array(
			"connectionString" => "mysql:host=localhost;dbname=parteitool",
			"emulatePrepare"   => true,
			"username"         => "parteitool",
			"password"         => "strenggeheim",
			"charset"          => "utf8mb4",
			"enableProfiling"  => true
		),
		'errorHandler'   => array(
			// use 'veranstaltung/error' action to display errors
			'errorAction' => 'veranstaltung/error',
		),
		'log'            => array(
			'class'  => 'CLogRouter',
			'routes' => array(
				array(
					'class'  => 'CFileLogRoute',
					'levels' => 'error, warning',
				),
				/*
				array(
					'class'=> 'CWebLogRoute',
				),
				*/
			),
		),
		'bootstrap'      => array(
			'class' => 'composer.chris83.yii-bootstrap.components.Bootstrap',
		),
		'datetimepicker' => array(
			'class' => 'ext.datetimepicker.EDateTimePicker',
		),
		'loid'           => array(
			'class' => 'application.extensions.lightopenid.loid',
		),
	),

	'params'         => array(
		'standardVeranstaltungsreihe' => "default",
		'pdf_logo'                    => 'LOGO_PFAD',
		'kontakt_email'               => 'EMAILADRESSE',
		'mail_from'                   => mb_encode_mimeheader('Antragsgrün') . ' <EMAILADRESSE>',
		'mail_from_name'              => 'Antragsgrün',
		'mail_from_email'             => 'EMAILADRESSE',
		'admin_user_id'               => null,
		'odt_default_template'        => __DIR__ . '/../../docs/OpenOffice-Template.odt',
	),
);
