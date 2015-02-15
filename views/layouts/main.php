<?php
use app\components\UrlHelper;
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

    $searchUrl = UrlHelper::createUrl('consultation/search');
    echo Html::beginForm($searchUrl, 'get', ['class' => 'form-search visible-xs-inline-block']);
    echo '<input type="hidden" name="id" value="">';
    echo '<div class="input-append">' .
        '<input class="search-query" type="search" name="suchbegriff" value="" autofocus placeholder="Suche">' .
        '<button type="submit" class="btn"><i style="height: 18px;" class="icon-search"></i></button></div>';
    echo Html::endForm();

    echo '<ul class="nav navbar-nav">';
    echo '<li class="active">' . Html::a('Start', UrlHelper::createUrl("consultation/index")) . '</li>';
    echo '<li>' . Html::a('Hilfe', UrlHelper::createUrl("consultation/help")) . '</li>';

    if (!$controller->getCurrentUser() && !$minimalistic) {
        $loginUrl = UrlHelper::createUrl(['user/login', 'backUrl' => \yii::$app->request->url]);
        echo '<li>' . Html::a('Login', $loginUrl) . '</li>';
    }
    if ($controller->getCurrentUser()) {
        $logoutUrl = UrlHelper::createUrl(['user/logout', 'backUrl' => \yii::$app->request->url]);
        echo '<li>' . Html::a('Logout', $logoutUrl) . '</li>';
    }
    if ($controller->consultation && $controller->consultation->isAdminCurUser()) {
        $adminUrl = UrlHelper::createUrl("admin/index");
        echo '<li><a href="' . Html::encode($adminUrl) . '">Admin</a></li>';
    }
    echo '</ul>
            </div>
        </div>
    </div>';

    echo '</div>';
}

if ($controller->consultation) {
    $homeUrl = UrlHelper::createUrl("consultation/index");
} else {
    $homeUrl = Url::toRoute("manager/index");
}
echo '<div class="row logo"><a href="' . Html::encode($homeUrl) . '" title="Startseite">';
if ($controller->consultation && $controller->consultation->getSettings()->logoUrl != "") {
    $path     = parse_url($controller->consultation->getSettings()->logoUrl);
    $filename = basename($path["path"]);
    $filename = substr($filename, 0, strrpos($filename, "."));
    $filename = str_replace(
        array("_", "ue", "ae", "oe", "Ue", "Oe", "Ae"),
        array(" ", "ü", "ä", "ö", "Ü" . "Ö", "Ä"),
        $filename
    );
    $logoUrl = $controller->consultation->getSettings()->logoUrl;
    echo '<img src="' . Html::encode($logoUrl) . '" alt="' . Html::encode($filename) . '">';
} else {
    echo '<span class="logo_img"></span>';
}
echo '</a></div>';


if (is_array($params->breadcrumbs)) {
    echo '<ol class="breadcrumb">';
    echo '<li>' . Html::a($params->breadcrumbsTopname, '/') . '</li>';
    foreach ($params->breadcrumbs as $link => $name) {
        echo '<li>' . Html::a($name, $link) . '</li>';
    }
    echo '</ol>';
}


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
