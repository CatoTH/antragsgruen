<?php

mb_internal_encoding("UTF-8");

define("SEED_KEY", "randomkey");

Yii::setPathOfAlias('bootstrap', dirname(__FILE__) . '/../../vendor/chris83/yii-bootstrap');

$dom  = "http://<veranstaltungsreihe_id:[\w_-]+>.antraege-v2.hoessl.eu/";
$domv = $dom . "<veranstaltung_id:[\w_-]+>/";

return array(
	'basePath'   => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
	'name'       => 'Grüne Anträge',

	'preload'    => array(
		'log',
		'bootstrap',
	),

	'import'     => array(
		'application.models.*',
		'application.components.*',
		'ext.giix-components.*',
	),

	'modules'    => array(),

	// application components
	'components' => array(
		'user'           => array(
			// enable cookie-based authentication
			'allowAutoLogin' => true,
			'loginUrl'       => array('veranstaltung/login'),
		),
		'cache'=>array(
			'class'=>'system.caching.CFileCache',
		),
		'urlManager'     => array(
			'urlFormat'      => 'path',
			'showScriptName' => false,
			'rules'          => array(
				$domv . 'admin/'                                                                          => 'admin/index',
				$domv . 'admin/veranstaltungen/'                                                          => 'admin/veranstaltungen/update',
				$domv . 'admin/veranstaltungen/<_a:(index|create|update|delete|view|admin)>'              => 'admin/veranstaltungen/<_a>',
				$domv . 'admin/antraege'                                                                  => 'admin/antraege',
				$domv . 'admin/antraege/<_a:(index|create|update|delete|view|admin)>'                     => 'admin/antraege/<_a>',
				$domv . 'admin/aenderungsantraege'                                                        => 'admin/aenderungsantraege',
				$domv . 'admin/aenderungsantraege/<_a:(index|create|update|delete|view|admin)>'           => 'admin/aenderungsantraege/<_a>',
				$domv . 'admin/antraegeKommentare'                                                        => 'admin/antraegeKommentare',
				$domv . 'admin/antraegeKommentare/<_a:(index|create|update|delete|view|admin)>'           => 'admin/antraegeKommentare/<_a>',
				$domv . 'admin/aenderungsantraegeKommentare'                                              => 'admin/aenderungsantraegeKommentare',
				$domv . 'admin/aenderungsantraegeKommentare/<_a:(index|create|update|delete|view|admin)>' => 'admin/aenderungsantraegeKommentare/<_a>',
				$domv . 'admin/texte'                                                                     => 'admin/texte',
				$domv . 'admin/texte/<_a:(index|create|update|delete|view|admin)>'                        => 'admin/texte/<_a>',
				$domv . 'admin/kommentare_excel'                                                          => 'admin/index/kommentareexcel',
				$domv . 'hilfe'                                                                           => 'veranstaltung/hilfe',
				$domv . 'suche'                                                                           => 'veranstaltung/suche',
				$domv . 'impressum'                                                                       => 'veranstaltung/impressum',
				$domv . 'pdfs'                                                                            => 'veranstaltung/pdfs',
				$domv . 'login'                                                                           => 'veranstaltung/login',
				$domv . 'logout'                                                                          => 'veranstaltung/logout',
				$domv . 'feedAlles'                                                                       => 'veranstaltung/feedAlles',
				$domv . 'feedAntraege'                                                                    => 'veranstaltung/feedAntraege',
				$domv . 'feedAenderungsantraege'                                                          => 'veranstaltung/feedAenderungsantraege',
				$domv . 'feedKommentare'                                                                  => 'veranstaltung/feedKommentare',
				$domv . 'antrag/neu'                                                                      => 'antrag/neu',
				$domv . 'antrag/<antrag_id:\d+>?kommentar_id=<kommentar_id:\d+>'                          => 'antrag/anzeige',
				$domv . 'antrag/<antrag_id:\d+>'                                                          => 'antrag/anzeige',
				$domv . 'antrag/<antrag_id:\d+>/pdf'                                                      => 'antrag/pdf',
				$domv . 'antrag/<antrag_id:\d+>/neuConfirm'                                               => 'antrag/neuConfirm',
				$domv . 'antrag/<antrag_id:\d+>/aendern'                                                  => 'antrag/aendern',
				$domv . 'antrag/<antrag_id:\d+>/aenderungsantrag/<aenderungsantrag_id:\d+>'               => 'aenderungsantrag/anzeige',
				$domv . 'antrag/<antrag_id:\d+>/aenderungsantrag/<aenderungsantrag_id:\d+>/neuConfirm'    => 'aenderungsantrag/neuConfirm',
				$domv . 'antrag/<antrag_id:\d+>/aenderungsantrag/<aenderungsantrag_id:\d+>/pdf'           => 'aenderungsantrag/pdf',
				$domv . 'antrag/<antrag_id:\d+>/aenderungsantrag/<aenderungsantrag_id:\d+>/pdf_diff'      => 'aenderungsantrag/pdfDiff',
				$domv . 'antrag/<antrag_id:\d+>/aenderungsantrag/neu'                                     => 'aenderungsantrag/neu',
				$domv . 'antrag/<antrag_id:\d+>/aenderungsantrag/ajaxCalcDiff'                            => 'aenderungsantrag/ajaxCalcDiff',
				$domv                                                                                     => 'veranstaltung/index',
				$dom                                                                                      => 'veranstaltung/index',
				$dom_plain                                                                                => 'infos/selbstEinsetzen',
				$dom_plain . 'selbst-einsetzen'                                                           => 'infos/selbstEinsetzen',
				$dom_plain . 'impressum'                                                                  => 'infos/impressum',
			),
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
			"charset"          => "utf8",
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

	'params'     => array(
		'standardVeranstaltungsreihe'     => 1,
		'standardVeranstaltungAutoCreate' => true,
		'pdf_logo'                        => 'LOGO_PFAD',
		'kontakt_email'                   => 'EMAILADRESSE',
		'mail_from'                       => 'Antragsgrün <EMAILADRESSE>',
		'font_css'                        => '/css/font.css',
	),
);
