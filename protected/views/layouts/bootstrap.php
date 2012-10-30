<?php
/**
 * @var CController $this
 */
?><!DOCTYPE HTML>
<html lang="de">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

	<title><?php echo CHtml::encode($this->pageTitle); ?></title>

    <!-- ### neu ### -->
    <!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
    <link href="//cloud.webtype.com/css/a47fbad3-f89b-4172-b851-d8bd6b49eb71.css" rel="stylesheet" type="text/css" />
    <!--[if lt IE 8]><link rel="stylesheet" href="/css/antraege-ie7.css"><![endif]-->
    <!-- ### /neu ### -->

</head>

<body>

<div class="container" id="page">
	<div id="mainmenu">
		<?php

		/*
		 			array('label'   => 'Einstellungen',
				  'url'     => array("/einstellungen/"),
				  "visible" => !Yii::app()->user->isGuest),
		 */
		$items = array(
			array('label'=> 'Start',
				  'url'  => array('/site/index')),
			array('label'=> 'Hilfe',
				  'url'  => array('/site/hilfe')),
			array('label'=> 'Administration',
				  'url'  => array('/admin/'),
				'visible' => (Yii::app()->user->getState("role") == "admin"),
			),
			array('label'  => 'Login',
				  'url'    => array('/site/login'),
				  'visible'=> Yii::app()->user->isGuest),
			array('label'  => 'Logout',
				  'url'    => array('/site/logout'),
				  'visible'=> !Yii::app()->user->isGuest)
		);

		$this->widget('bootstrap.widgets.TbNavbar', array(
		'fixed'   => false,
		'brand'   => "",
		'brandUrl'=> '/',
		'collapse'=> true,
		'items'   => array(
			array(
				'class'=> 'bootstrap.widgets.TbMenu',

				'items'=> $items,
			)
		),
	)); ?>
	</div>

    <a href="/" class="logo"><img src="/css/img/logo.png" alt="Antragsgrün"></a>

	<!-- mainmenu -->
	<?php if (isset($this->breadcrumbs)): ?>
		<?php
		$top_name = (isset($this->breadcrumbs_topname) && $this->breadcrumbs_topname !== null ? $this->breadcrumbs_topname : "Anträge");
		$this->widget('bootstrap.widgets.TbBreadcrumbs', array(
			'homeLink' => CHtml::link($top_name, "/"),
			'links'=> $this->breadcrumbs,
		)); ?><!-- breadcrumbs -->
	<?php endif?>

	<?php
	$this->widget('bootstrap.widgets.TbAlert');
	/** @var string $content */
	echo $content;

	?>

	<div style="clear: both; padding-top: 15px;"></div>

	<?php $this->widget('bootstrap.widgets.TbNavbar', array(
	'htmlOptions'=> array(
		'class'=> 'footer_bar',
	),
	'fixed'   => false,
	'brand'   => "",
	'collapse'=> true,
		'items'   => array('<a href="/site/impressum">Impressum</a>'),
	)); ?>

	<!-- footer -->

</div>
<!-- page -->

</body>
</html>
