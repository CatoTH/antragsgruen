<?php
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var \yii\web\View $this
 * @var string $content
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;

$row_classes = array();
if (isset($controller->text_comments) && $controller->text_comments) $row_classes[] = "text_comments";

$minimalistic = ($controller->consultation && $controller->consultation->getSettings()->minimalistic_interface);

$this->beginPage();

?><!DOCTYPE HTML>
<html lang="de">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo Html::encode(isset($this->title) ? $this->title : ''); ?></title>
    <?php

    if ($controller->consultation && $controller->consultation->getSettings()->fb_logo_url != "") {
        echo '<link rel="image_src" href="' . Html::encode($controller->consultation->getSettings()->fb_logo_url) . '">';
    }
    if ($controller->robots_noindex) {
        echo '<meta name="robots" content="noindex, nofollow">' . "\n";
    }


    ?>
    <!--[if lt IE 9]>
    <script src="/js/html5.js"></script>
    <![endif]-->
    <!--[if lt IE 8]>
    <link rel="stylesheet" href="/css/antragsgruen-ie7.css">
    <![endif]-->


    <link rel="stylesheet" href="/css/antragsgruen.css">


    <!--[if lt IE 9]>
    <script src="/js/jquery-1.11.2.min.js"></script>
    <![endif]-->
    <!--[if gte IE 9]><!-->
    <script src="/js/jquery-2.1.3.min.js"></script>
    <!--<![endif]-->

    <script src="/js/jquery-2.1.3.min.js"></script>


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
    /*
    if ($controller->veranstaltung) foreach (veranstaltungsspezifisch_css_files($controller->veranstaltung) as $css_file) {
        echo '<link rel="stylesheet" href="' . Html::encode($css_file) . '">' . "\n";
    }
    */
    ?>
</head>

<body <?php if (count($row_classes) > 0) echo "class='" . implode(" ", $row_classes) . "'"; ?>>
<script src="/js/modernizr.js"></script>
<?php $this->beginBody() ?>

<div class="container" id="page">
    <div id="mainmenu">
        <div class="navbar">
            <div class="navbar-inner">
                <div class="container">
                    <?php if ($controller->consultation) { ?>
                        <form class='form-search visible-phone'
                              action='<?= Html::encode(Url::toRoute("consultation/search")) ?>' method='GET'>
                            <input type='hidden' name='id' value=''>
                            <?php
                            echo "<div class='input-append'><input class='search-query' type='search' name='suchbegriff' value='' autofocus placeholder='Suche'><button type='submit' class='btn'><i style='height: 18px;' class='icon-search'></i></button></div>";
                            ?>
                        </form>

                        <ul class="nav">
                            <li class="active"><a
                                    href="<?= Html::encode(Url::toRoute("consultation/index")) ?>">Start</a></li>
                            <li><a href="<?= Html::encode(Url::toRoute("consultation/help")) ?>">Hilfe</a></li>
                            <?php
                            /* @TODO
                            if (Yii::app()->user->isGuest && !$minimalistic) { ?>
                             * <li><a href="<?= Html::encode($controller->createUrl("veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri))) ?>">Login</a></li>
                             * <?php
                             * }
                             * if (!Yii::app()->user->isGuest) {
                             * ?>
                             * <li><a href="<?= Html::encode($controller->createUrl("veranstaltung/logout", array("back" => yii::app()->getRequest()->requestUri))) ?>">Logout</a></li>
                             * <?php
                             * }
                             * if ($controller->veranstaltung != null && $controller->veranstaltung->isAdminCurUser()) {
                             * ?>
                             * <li><a href="<?= Html::encode($controller->createUrl("admin/index")) ?>">Admin</a></li>
                             * <?php }
                             */
                            ?>
                        </ul>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>


    <a href="<?php echo Html::encode($controller->consultation ? Url::toRoute("consultation/index") : Url::toRoute("manager/index")); ?>"
       class="logo"><?php
        if ($controller->consultation && $controller->consultation->getSettings()->logo_url != "") {
            $path     = parse_url($controller->consultation->getSettings()->logo_url);
            $filename = basename($path["path"]);
            $filename = substr($filename, 0, strrpos($filename, "."));
            $filename = str_replace(array("_", "ue", "ae", "oe", "Ue", "Oe", "Ae"), array(" ", "ü", "ä", "ö", "Ü" . "Ö", "Ä"), $filename);
            echo '<img src="' . Html::encode($controller->consultation->getSettings()->logo_url) . '" alt="' . Html::encode($filename) . '">';
        } else {
            echo '<img src="/img/logo.png" alt="Antragsgrün">';
        }
        ?></a>

    <?php
    /*
    if (isset($controller->breadcrumbs)): ?>
        <?php
        $breadcrumbs = array();
        foreach ($controller->breadcrumbs as $key => $val) if ($key !== "" && !($key === 0 && $val === "")) $breadcrumbs[$key] = $val;
        $top_name = (isset($controller->breadcrumbs_topname) && $controller->breadcrumbs_topname !== null ? $controller->breadcrumbs_topname : "Start");
        $controller->widget('bootstrap.widgets.TbBreadcrumbs', array(
            'homeLink' => Html::link($top_name, "/"),
            'links'    => $breadcrumbs,
        ));
        if (count($breadcrumbs) == 0) echo "<br><br>";
        ?>
    <?php endif ?>

    <?php
    */

    /** @var string $content */
    echo $content;

    ?>

    <div style="clear: both; padding-top: 15px;"></div>


    <footer class="navbar" id="footer_navbar">
        <div class="container">
            <ul class="nav navbar-nav">
                <li><a href="<?= Html::encode($controller->consultation ? Url::toRoute("consultation/legal") : Url::toRoute("manager/legal")) ?>" style="font-weight: bold; color: white;">Impressum</a></li>
                <li>&nbsp; <small>Antragsgrün von <a href="https://www.hoessl.eu/">Tobias Hößl</a>, Version <a href="https://github.com/CatoTH/antragsgruen/blob/master/History.md"><?=ANTRAGSGRUEN_VERSION?></a></small></li>
                </ul>
        </div>
    </footer>
</div>

<script src="/js/bootstrap.js"></script>
<script src="/js/antragsgruen.js"></script>
<?php $this->endBody() ?>
</body>
</html>
<?
$this->endPage();
