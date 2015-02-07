<?php
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var \yii\web\View $this
 * @var string $content
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;

$body_classes = array();
/*
if (isset($controller->text_comments) && $controller->text_comments) {
    $row_classes[] = "text_comments";
}
*/

$minimalistic = ($controller->consultation && $controller->consultation->getSettings()->minimalisticUI);

$this->beginPage();

echo '<!DOCTYPE HTML>
<html lang="de">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
echo '<title>' . Html::encode(isset($this->title) ? $this->title : '') . '</title>' . "\n";
echo Html::csrfMetaTags();

if ($controller->consultation && $controller->consultation->getSettings()->logoUrlFB != "") {
    echo '<link rel="image_src" href="' . Html::encode($controller->consultation->getSettings()->logoUrlFB) . '">';
}
if ($params->robotsNoindex) {
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
<?php
foreach ($params->extraCss as $file) {
    echo '<link rel="stylesheet" href="' . Html::encode($file) . '">' . "\n";
}
?>

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

echo '</head>';

echo '<body ' . (count($body_classes) > 0 ? 'class="' . implode(" ", $body_classes) . '"' : '') . '>';

echo '<script src="/js/modernizr.js"></script>';

$this->beginBody();

echo '<div class="over_footer_wrapper">';
echo '<div class="container" id="page">';
if ($controller->consultation) {
    echo '<div id="mainmenu">';
    echo '<div class="navbar">
        <div class="navbar-inner">
            <div class="container">';

    Html::beginForm($controller->createUrl('consultation/search'), 'get', ['class' => 'form-search visible-phone']);
    echo '<input type="hidden" name="id" value="">';
    echo '<div class="input-append">' .
        '<input class="search-query" type="search" name="suchbegriff" value="" autofocus placeholder="Suche">' .
        '<button type="submit" class="btn"><i style="height: 18px;" class="icon-search"></i></button></div>';
    Html::endForm();

    echo '<ul class="nav">
        <li class="active"><a href="' . Html::encode($controller->createUrl("consultation/index")) . '">Start</a></li>
        <li><a href="' . Html::encode($controller->createUrl("consultation/help")) . '">Hilfe</a></li>
        ';

    /* @TODO
    if (Yii::app()->user->isGuest && !$minimalistic) { ?>
     * <li><a href="<?= Html::encode($controller->createUrl("veranstaltung/login",
     * array("back" => yii::app()->getRequest()->requestUri))) ?>">Login</a></li>
     * <?php
     * }
     * if (!Yii::app()->user->isGuest) {
     * ?>
     * <li><a href="<?= Html::encode($controller->createUrl("veranstaltung/logout",
     * array("back" => yii::app()->getRequest()->requestUri))) ?>">Logout</a></li>
     * <?php
     * }
     * if ($controller->veranstaltung != null && $controller->veranstaltung->isAdminCurUser()) {
     * ?>
     * <li><a href="<?= Html::encode($controller->createUrl("admin/index")) ?>">Admin</a></li>
     * <?php }
     */

    echo '</ul>
            </div>
        </div>
    </div>';

    echo '</div>';
}


/*
if (isset($controller->breadcrumbs)): ?>
    <?php
    $breadcrumbs = array();
    foreach ($controller->breadcrumbs as $key => $val) {
        if ($key !== "" && !($key === 0 && $val === "")) $breadcrumbs[$key] = $val;
    }
    $top_name = (isset($controller->breadcrumbs_topname) && $controller->breadcrumbs_topname !== null ?
        $controller->breadcrumbs_topname : "Start");
    $controller->widget('bootstrap.widgets.TbBreadcrumbs', array(
        'homeLink' => Html::link($top_name, "/"),
        'links'    => $breadcrumbs,
    ));
    if (count($breadcrumbs) == 0) echo "<br><br>";
    ?>
<?php endif ?>

<?php
*/

$home_url = ($controller->consultation ? Url::toRoute("consultation/index") : Url::toRoute("manager/index"));
echo '<div class="row logo"><a href="' . Html::encode($home_url) . '" title="Startseite">';
if ($controller->consultation && $controller->consultation->getSettings()->logoUrl != "") {
    $path     = parse_url($controller->consultation->getSettings()->logoUrl);
    $filename = basename($path["path"]);
    $filename = substr($filename, 0, strrpos($filename, "."));
    $filename = str_replace(
        array("_", "ue", "ae", "oe", "Ue", "Oe", "Ae"),
        array(" ", "ü", "ä", "ö", "Ü" . "Ö", "Ä"),
        $filename
    );
    $logo_url = $controller->consultation->getSettings()->logoUrl;
    echo '<img src="' . Html::encode($logo_url) . '" alt="' . Html::encode($filename) . '">';
} else {
    echo '<span class="logo_img"></span>';
}
echo '</a></div>';


/** @var string $content */
echo $content;

$legal_link = ($controller->consultation ? Url::toRoute("consultation/legal") : Url::toRoute("manager/legal"));

echo '<div style="clear: both; padding-top: 15px;"></div>
<div class="footer_spacer"></div>
</div></div></div>';
?>


    <footer class="footer">
        <div class="container">
            <a href="<?= Html::encode($legal_link) ?>" class="legal">Impressum</a>

            <span class="version">
                Antragsgrün von <a href="https://www.hoessl.eu/">Tobias Hößl</a>,
                Version <?= Html::a(ANTRAGSGRUEN_VERSION, ANTRAGSGRUEN_HISTORY_URL) ?>
            </span>
        </div>
    </footer>

    <script src="/js/bootstrap.js"></script>
    <script src="/js/antragsgruen.js"></script>
<?php
foreach ($params->extraJs as $file) {
    echo '<script src="' . Html::encode($file) . '"></script>' . "\n";
}
foreach ($params->onloadJs as $js) {
    echo '<script>' . $js . '</script>' . "\n";
}


$this->endBody();
echo '</body></html>';

$this->endPage();
