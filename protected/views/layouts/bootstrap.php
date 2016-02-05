<?php
/**
 * @var AntragsgruenController $this
 */
$row_classes = array();
if (isset($this->text_comments) && $this->text_comments) $row_classes[] = "text_comments";

$minimalistisch = (is_a($this->veranstaltung, "Veranstaltung") && $this->veranstaltung->getEinstellungen()->ansicht_minimalistisch);


$assets_base = $this->getAssetsBase();

/** @var CWebApplication $app */
$app = Yii::app();
/** @var CClientScript $cs */
$cs = $app->getClientScript();

/** @var Bootstrap $boot */
$boot = $app->getComponent("bootstrap");
$boot->registerCoreCss();
$cs->registerCoreScript('jquery');
$cs->registerCssFile($assets_base . '/css/antraege.css');
$cs->registerCssFile($assets_base . '/css/antraege-print.css', 'print');
$cs->registerScriptFile($assets_base . '/js/modernizr.js');
$cs->registerScriptFile($assets_base . '/js/antraege.js', CClientScript::POS_END);

?><!DOCTYPE HTML>
<html lang="de">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title><?php echo CHtml::encode($this->pageTitle); ?></title>
	<?php
	if ($this->pageDescription != '') {
		echo '<meta name="description" content="' . CHtml::encode($this->pageDescription) . '">' . "\n";
	}
	if (is_a($this->veranstaltung, "Veranstaltung") && $this->veranstaltung->getEinstellungen()->fb_logo_url != "") {
		echo '<link rel="image_src" href="' . CHtml::encode($this->veranstaltung->getEinstellungen()->fb_logo_url) . '">';
	}
	if ($this->robots_noindex) {
		echo '<meta name="robots" content="noindex, nofollow">' . "\n";
	}
	?>
	<!--[if lt IE 9]>
	<script src="<?php echo $assets_base; ?>/js/html5.js"></script><![endif]-->
	<!--[if lt IE 8]>
	<link rel="stylesheet" href="<?php echo $assets_base; ?>/css/antraege-ie7.css"><![endif]-->

	<link rel="apple-touch-icon" sizes="57x57" href="/apple-touch-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="114x114" href="/apple-touch-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="72x72" href="/apple-touch-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="144x144" href="/apple-touch-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="60x60" href="/apple-touch-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="120x120" href="/apple-touch-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="76x76" href="/apple-touch-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon-152x152.png">
	<link rel="icon" type="image/png" href="/favicon-196x196.png" sizes="196x196">
	<link rel="icon" type="image/png" href="/favicon-160x160.png" sizes="160x160">
	<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
	<link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
	<link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
	<meta name="msapplication-TileColor" content="#e6e6e6">
	<meta name="msapplication-TileImage" content="/mstile-144x144.png">
	<?php
	if ($this->veranstaltung) foreach (veranstaltungsspezifisch_css_files($this->veranstaltung) as $css_file) {
		echo '<link rel="stylesheet" href="' . CHtml::encode($css_file) . '">' . "\n";
	}
	?>
</head>

<body <?php if (count($row_classes) > 0) echo "class='" . implode(" ", $row_classes) . "'"; ?>>

<section style="background: #fafafa; padding: 5px; border-bottom: solid 1px #e0e0e0;">
	<a href="https://antragsgruen.de/"><strong>Antragsgrün</strong> <span style="color: black;">- die Online-Antragsverwaltung für Parteitage, Verbandstagungen und Mitgliederversammlungen</span></a>
</section>

<div class="container" id="page">
	<div id="mainmenu">
		<div class="navbar">
			<div class="navbar-inner">
				<div class="container">
					<?php if ($this->veranstaltung) { ?>
						<form class='form-search visible-phone' action='<?= CHtml::encode($this->createUrl("veranstaltung/suche")) ?>' method='GET'>
							<input type='hidden' name='id' value=''>
							<?php
							echo "<div class='input-append'><input class='search-query' type='search' name='suchbegriff' value='' autofocus placeholder='Suche'><button type='submit' class='btn'><i style='height: 18px;' class='icon-search'></i></button></div>";
							?>
						</form>

						<ul class="nav">
							<li class="active"><a href="<?= CHtml::encode($this->createUrl("veranstaltung/index")) ?>">Start</a></li>
							<li><a href="<?= CHtml::encode($this->createUrl("veranstaltung/hilfe")) ?>">Hilfe</a></li>
							<?php if (Yii::app()->user->isGuest && !$minimalistisch) { ?>
								<li><a href="<?= CHtml::encode($this->createUrl("veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri))) ?>">Login</a></li>
							<?php
							}
							if (!Yii::app()->user->isGuest) {
								?>
								<li><a href="<?= CHtml::encode($this->createUrl("veranstaltung/logout", array("back" => yii::app()->getRequest()->requestUri))) ?>">Logout</a></li>
							<?php
							}
							if ($this->veranstaltung != null && $this->veranstaltung->isAdminCurUser()) {
								?>
								<li><a href="<?= CHtml::encode($this->createUrl("admin/index")) ?>">Admin</a></li>
							<?php } ?>
						</ul>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>


	<a href="<?php echo CHtml::encode($this->veranstaltung ? $this->createUrl("veranstaltung/index") : $this->createUrl("infos/selbstEinsetzen")); ?>" class="logo"><?php
		if (is_a($this->veranstaltung, "Veranstaltung") && $this->veranstaltung->getEinstellungen()->logo_url != "") {
			$path     = parse_url($this->veranstaltung->getEinstellungen()->logo_url);
			$filename = basename($path["path"]);
			$filename = substr($filename, 0, strrpos($filename, "."));
			$filename = str_replace(array("_", "ue", "ae", "oe", "Ue", "Oe", "Ae"), array(" ", "ü", "ä", "ö", "Ü" . "Ö", "Ä"), $filename);
			echo '<img src="' . CHtml::encode($this->veranstaltung->getEinstellungen()->logo_url) . '" alt="' . CHtml::encode($filename) . '">';
		} else {
			echo '<img src="' . $assets_base . '/img/logo.png" alt="Antragsgrün">';
		}
		?></a>

	<?php if (isset($this->breadcrumbs)): ?>
		<?php
		$breadcrumbs = array();
		foreach ($this->breadcrumbs as $key => $val) if ($key !== "" && !($key === 0 && $val === "")) $breadcrumbs[$key] = $val;
		$top_name = (isset($this->breadcrumbs_topname) && $this->breadcrumbs_topname !== null ? $this->breadcrumbs_topname : "Start");
		$this->widget('bootstrap.widgets.TbBreadcrumbs', array(
			'homeLink' => CHtml::link($top_name, "/"),
			'links'    => $breadcrumbs,
		));
		if (count($breadcrumbs) == 0) echo "<br><br>";
		?>
	<?php endif ?>

	<?php
	/** @var string $content */
	echo $content;

	?>

	<div style="clear: both; padding-top: 15px;"></div>

	<?php
	$impressums_link = $this->veranstaltung ? $this->createUrl("veranstaltung/impressum") : $this->createUrl("infos/impressum");
	$version = CHtml::encode(ANTRAGSGRUEN_VERSION);
	$this->widget('bootstrap.widgets.TbNavbar', array(
		'htmlOptions' => array(
			'class' => 'footer_bar',
		),
		'fixed'       => false,
		'brand'       => "",
		'collapse'    => false,
		'items'       => array(
			'<a href="' . CHtml::encode($impressums_link) . '">Impressum</a>',
			' &nbsp; <small>Antragsgrün von <a href="https://www.hoessl.eu/">Tobias Hößl</a>, Version <a href="https://github.com/CatoTH/antragsgruen/blob/master/History.md">' . $version . '</a></small>',
		),
	));
	?>
</div>
</body>
</html>
