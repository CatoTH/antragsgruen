<?php
/**
 * @var CController $this
 */
$row_classes = array();
if (isset($this->text_comments) && $this->text_comments) $row_classes[] = "text_comments";

?><!DOCTYPE HTML>
<html lang="de">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title><?php echo CHtml::encode($this->pageTitle); ?></title>

	<!-- ### neu ### -->
	<!--[if lt IE 9]>
	<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
	<link href="//cloud.webtype.com/css/a47fbad3-f89b-4172-b851-d8bd6b49eb71.css" rel="stylesheet" type="text/css"/>
	<!--[if lt IE 8]>
	<link rel="stylesheet" href="/css/antraege-ie7.css"><![endif]-->
	<!-- ### /neu ### -->

</head>

<body <?php if (count($row_classes) > 0) echo "class='" . implode(" ", $row_classes) . "'"; ?>>

<div class="container" id="page">
	<div id="mainmenu">
		<div class="navbar">
			<div class="navbar-inner">
				<div class="container">
					<form class='form-search visible-phone' action='/site/suche/' method='GET'>
						<input type='hidden' name='id' value=''>
						<div class='input-append'><input class='search-query' type='search' name='suchbegriff' value='' autofocus placeholder='Suche'><button type='submit' class='btn'><i style='height: 18px;' class='icon-search'></i></button></div>
					</form>

					<ul class="nav">
						<li class="active"><a href="/site/index">Start</a></li>
						<li><a href="/site/hilfe">Hilfe</a></li>
						<?php if (Yii::app()->user->isGuest) { ?>
						<li><a href="/site/login">Login</a></li>
						<?php } else { ?>
						<li><a href="/site/logout">Logout</a></li>
						<?php
					}
						if (Yii::app()->user->getState("role") == "admin") {
							?>
							<li><a href="/admin">Admin</a></li>
							<?php } ?>
					</ul>
				</div>
			</div>
		</div>
	</div>


	<a href="/" class="logo"><img src="/css/img/logo.png" alt="Antragsgrün"></a>

	<!-- mainmenu -->
	<?php if (isset($this->breadcrumbs)): ?>
		<?php
		$top_name = (isset($this->breadcrumbs_topname) && $this->breadcrumbs_topname !== null ? $this->breadcrumbs_topname : "Anträge");
		$this->widget('bootstrap.widgets.TbBreadcrumbs', array(
			'homeLink' => CHtml::link($top_name, "/"),
			'links'    => $this->breadcrumbs,
		)); ?><!-- breadcrumbs -->
	<?php endif?>

	<?php
	$this->widget('bootstrap.widgets.TbAlert');
	/** @var string $content */
	echo $content;

	?>

	<div style="clear: both; padding-top: 15px;"></div>

	<?php $this->widget('bootstrap.widgets.TbNavbar', array(
	'htmlOptions' => array(
		'class' => 'footer_bar',
	),
	'fixed'       => false,
	'brand'       => "",
	'collapse'    => false,
	'items'       => array('<a href="/site/impressum">Impressum</a>'),
)); ?>

	<!-- footer -->

</div>
<!-- page -->

</body>
</html>
