<?php
/**
 * @var AntragsgruenController $this
 */
$row_classes = array();
if (isset($this->text_comments) && $this->text_comments) $row_classes[] = "text_comments";

$minimalistisch = (is_a($this->veranstaltung, "Veranstaltung") && $this->veranstaltung->ansicht_minimalistisch);

?><!DOCTYPE HTML>
<html lang="de">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title><?php echo CHtml::encode($this->pageTitle); ?></title>
	<?php
	if (is_a($this->veranstaltung, "Veranstaltung") && $this->veranstaltung->fb_logo_url != "") {
		echo '<link rel="image_src" href="' . CHtml::encode($this->veranstaltung->fb_logo_url) . '">';
	}
	?>
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
					<form class='form-search visible-phone' action='<?=CHtml::encode($this->createUrl("site/suche"))?>' method='GET'>
						<input type='hidden' name='id' value=''>
						<div class='input-append'><input class='search-query' type='search' name='suchbegriff' value='' autofocus placeholder='Suche'><button type='submit' class='btn'><i style='height: 18px;' class='icon-search'></i></button></div>
					</form>

					<ul class="nav">
						<li class="active"><a href="<?=CHtml::encode($this->createUrl("site/veranstaltung"))?>">Start</a></li>
						<li><a href="<?=CHtml::encode($this->createUrl("site/hilfe"))?>">Hilfe</a></li>
						<?php if (Yii::app()->user->isGuest && !$minimalistisch) { ?>
						<li><a href="<?=CHtml::encode($this->createUrl("site/login", array("back" => yii::app()->getRequest()->requestUri)))?>">Login</a></li>
						<?php }
						if (!Yii::app()->user->isGuest) { ?>
						<li><a href="<?=CHtml::encode($this->createUrl("site/logout", array("back" => yii::app()->getRequest()->requestUri)))?>">Logout</a></li>
						<?php
					}
						if (Yii::app()->user->getState("role") == "admin" || ($this->veranstaltung != null && $this->veranstaltung->isAdminCurUser())) {
							?>
							<li><a href="<?=CHtml::encode($this->createUrl("admin/index"))?>">Admin</a></li>
							<?php } ?>
					</ul>
				</div>
			</div>
		</div>
	</div>


	<a href="<?php echo CHtml::encode($this->createUrl("site/veranstaltung")); ?>" class="logo"><?php
		if (is_a($this->veranstaltung, "Veranstaltung") && $this->veranstaltung->logo_url != "") {
			$path = parse_url($this->veranstaltung->logo_url);
			$filename = basename($path["path"]);
			$filename = substr($filename, 0, strrpos($filename, "."));
			$filename = str_replace(array("_", "ue", "ae", "oe", "Ue", "Oe", "Ae"), array(" ", "ü", "ä", "ö", "Ü". "Ö", "Ä"), $filename);
			echo '<img src="' . CHtml::encode($this->veranstaltung->logo_url) . '" alt="' . CHtml::encode($filename) . '">';
		}  else {
			echo '<img src="/css/img/logo.png" alt="Antragsgrün">';
		}
	?></a>

	<!-- mainmenu -->
	<?php if (isset($this->breadcrumbs)): ?>
		<?php
		$breadcrumbs = array();
		foreach ($this->breadcrumbs as $key=>$val) if ($key !== "" && !($key === 0 && $val === "")) $breadcrumbs[$key] = $val;
		$top_name = (isset($this->breadcrumbs_topname) && $this->breadcrumbs_topname !== null ? $this->breadcrumbs_topname : "Start");
		$this->widget('bootstrap.widgets.TbBreadcrumbs', array(
			'homeLink' => CHtml::link($top_name, $this->createUrl("site/veranstaltung")),
			'links'    => $breadcrumbs,
		));
		if (count($breadcrumbs) == 0) echo "<br><br>";
		?><!-- breadcrumbs -->
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
	'items'       => array('<a href="' . CHtml::encode($this->createUrl("site/impressum")) . '">Impressum</a>'),
)); ?>

	<!-- footer -->

</div>
<!-- page -->

</body>
</html>
