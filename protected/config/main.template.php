<?php

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');

// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.

mb_internal_encoding("UTF-8");

return array(
	'basePath'  => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
	'name'      => 'Grüne Anträge',

	// preloading 'log' component
	'preload'   => array(
		'log',
		'bootstrap',
	),

	// autoloading model and component classes
	'import'    => array(
		'application.models.*',
		'application.components.*',
		'ext.giix-components.*',
	),

	'modules'   => array(
		// uncomment the following to enable the Gii tool
		/*
		'gii'=> array(
			'class'          => 'system.gii.GiiModule',
			'password'       => 'verysecurepassword',
			// If removed, Gii defaults to localhost only. Edit carefully to taste.
			'ipFilters'      => array('*', '::1'),
			'generatorPaths' => array(
				'ext.giix-core', // giix generators
				'bootstrap.gii',
			),
		),
		*/
	),

	// application components
	'components'=> array(
		'user'        => array(
			// enable cookie-based authentication
			'allowAutoLogin'=> true,
			'loginUrl'      => array('site/login'),
		),
		// uncomment the following to enable URLs in path-format
		'urlManager'  => array(
			'urlFormat'     => 'path',
			'showScriptName'=> false,
			'rules'         => array(
				'admin'                                 => 'admin/index',
				'<controller:\w+>/<id:\d+>'             => '<controller>/view',
				'<controller:\w+>/<action:\w+>/<id:\d+>'=> '<controller>/<action>',
				'<controller:\w+>/<action:\w+>'         => '<controller>/<action>',
			),
		),
		'authManager' => array(
			'class'       => 'CDbAuthManager',
			'connectionID'=> 'db',
		),
		
		'db'            => array(
			"connectionString" => "mysql:host=localhost;dbname=parteitool",
			"emulatePrepare"   => true,
			"username"         => "parteitool",
			"password"         => "strenggeheim",
			"charset"          => "utf8",
			"enableProfiling"  => true
		),
		'errorHandler'=> array(
			// use 'site/error' action to display errors
			'errorAction'=> 'site/error',
		),
		'log'         => array(
			'class' => 'CLogRouter',
			'routes'=> array(
				array(
					'class' => 'CFileLogRoute',
					'levels'=> 'error, warning',
				),
				/*
				array(
					'class'=> 'CWebLogRoute',
				),
				*/
			),
		),
		'bootstrap'=>array(
			'class'=>'ext.bootstrap.components.Bootstrap',
		),
		'datetimepicker'=>array(
			'class' => 'ext.datetimepicker.EDateTimePicker',
		),
		'loid'=>array(
			'class'=>'application.extensions.lightopenid.loid',
		),
		/*
	   'clientScript'=>array(
		   'class' => 'CClientScript',
		   'scriptMap' => array(
			   'jquery.js'=>false,
		   ),
		   'coreScriptPosition' => CClientScript::POS_BEGIN,
	   ),
		   */
	),

	// application-level parameters that can be accessed
	// using Yii::app()->params['paramName']
	'params'    => array(
		'adminEmail'=> 'meine@email.tld',
	),
);
