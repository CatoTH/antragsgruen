<?php
use app\components\UrlHelper;
use app\models\db\User;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var string $content
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$resourceBase = $controller->getParams()->resourceBase;

$bodyClasses = [];
if ($layout->fullScreen) {
    $bodyClasses[] = 'fullscreen';
}

$title = (isset($this->title) ? $this->title : '');
if (mb_strpos($title, 'Antragsgrün') === false) {
    $title .= ' (Antragsgrün)';
}

$minimalistic   = ($controller->consultation && $controller->consultation->getSettings()->minimalisticUI);
$controllerBase = ($controller->consultation ? 'consultation/' : 'manager/');
$lang           = Yii::$app->language;

$this->beginPage();


echo '<!DOCTYPE HTML>
<html lang="' . Html::encode($lang) . '">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
echo '<title>' . Html::encode($title) . '</title>' . "\n";
echo Html::csrfMetaTags();

if ($controller->consultation && $controller->consultation->getSettings()->logoUrlFB != "") {
    echo '<link rel="image_src" href="' . Html::encode($controller->consultation->getSettings()->logoUrlFB) . '">';
}
if ($layout->robotsNoindex) {
    echo '<meta name="robots" content="noindex, nofollow">' . "\n";
}

echo '<!--[if lt IE 9]>
    <script src="' . $resourceBase . 'js/bower/html5shiv/dist/html5shiv.min.js"></script>
    <![endif]-->
    <!--[if lt IE 8]>
    <link rel="stylesheet" href="' . $resourceBase . 'css/antragsgruen-ie7.css">
    <![endif]-->
';

echo '<link rel="stylesheet" href="' . $resourceBase . 'css/' . $layout->mainCssFile . '.css">' . "\n";

foreach ($layout->extraCss as $file) {
    echo '<link rel="stylesheet" href="' . Html::encode($file) . '">' . "\n";
}

echo '<!--[if lt IE 9]>
    <script src="' . $resourceBase . 'js/jquery-1.11.3.min.js"></script>
    <![endif]-->
    <!--[if gte IE 9]><!-->
    <script src="' . $resourceBase . 'js/bower/jquery/dist/jquery.min.js"></script>
    <!--<![endif]-->

    <link rel="apple-touch-icon" sizes="57x57" href="' . $resourceBase . 'apple-touch-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="114x114" href="' . $resourceBase . 'apple-touch-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="72x72" href="' . $resourceBase . 'apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="144x144" href="' . $resourceBase . 'apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="60x60" href="' . $resourceBase . 'apple-touch-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="120x120" href="' . $resourceBase . 'apple-touch-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="76x76" href="' . $resourceBase . 'apple-touch-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="152x152" href="' . $resourceBase . 'apple-touch-icon-152x152.png">
    <link rel="icon" type="image/png" href="' . $resourceBase . 'favicon-196x196.png" sizes="196x196">
    <link rel="icon" type="image/png" href="' . $resourceBase . 'favicon-160x160.png" sizes="160x160">
    <link rel="icon" type="image/png" href="' . $resourceBase . 'favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/png" href="' . $resourceBase . 'favicon-16x16.png" sizes="16x16">
    <link rel="icon" type="image/png" href="' . $resourceBase . 'favicon-32x32.png" sizes="32x32">
    <meta name="msapplication-TileColor" content="#e6e6e6">
    <meta name="msapplication-TileImage" content="' . $resourceBase . 'mstile-144x144.png">
';

echo '</head>';

echo '<body ' . (count($bodyClasses) > 0 ? 'class="' . implode(" ", $bodyClasses) . '"' : '') . '>';

echo '<script src="' . $resourceBase . 'js/modernizr.js"></script>';

$this->beginBody();

echo '<div class="over_footer_wrapper">';
echo '<div class="container" id="page">';
echo '<header id="mainmenu">';
echo '<div class="navbar">
        <div class="navbar-inner">
            <div class="container">';

if ($controller->consultation) {
    $searchUrl = UrlHelper::createUrl('consultation/search');
    echo Html::beginForm($searchUrl, 'get', ['class' => 'form-search visible-xs-inline-block']);
    echo '<input type="hidden" name="id" value="">';
    echo '<div class="input-append">' .
        '<input class="search-query" type="search" name="search_term" value="" placeholder="Suchbegriff" required>' .
        '<button type="submit" class="btn" title="Suchen">' .
        '<i style="height: 18px;" class="icon-search"></i></button></div>';
    echo Html::endForm();
}

echo '<ul class="nav navbar-nav">';

if ($controller->consultation) {
    $homeUrl = UrlHelper::createUrl('consultation/index');
    echo '<li class="active">' . Html::a(Yii::t('base', 'Start'), $homeUrl) . '</li>';
    $helpLink = UrlHelper::createUrl('consultation/help');
    echo '<li>' . Html::a(Yii::t('base', 'Help'), $helpLink, ['id' => 'helpLink']) . '</li>';
} else {
    $startLink = UrlHelper::createUrl('manager/index');
    echo '<li class="active">' . Html::a(Yii::t('base', 'Start'), $startLink) . '</li>';
}


if (!User::getCurrentUser() && !$minimalistic) {
    if (get_class($controller) == \app\controllers\UserController::class) {
        $backUrl = UrlHelper::createUrl('consultation/index');
    } else {
        $backUrl = \yii::$app->request->url;
    }
    $loginUrl = UrlHelper::createUrl(['user/login', 'backUrl' => $backUrl]);
    echo '<li>' . Html::a('Login', $loginUrl, ['id' => 'loginLink']) . '</li>';
}
if (User::getCurrentUser()) {
    echo '<li>' . Html::a('Einstellungen', UrlHelper::createUrl('user/myaccount'), ['id' => 'myAccountLink']) . '</li>';

    $logoutUrl = UrlHelper::createUrl(['user/logout', 'backUrl' => \yii::$app->request->url]);
    echo '<li>' . Html::a('Logout', $logoutUrl, ['id' => 'logoutLink']) . '</li>';
}
if (User::currentUserHasPrivilege($controller->consultation, User::PRIVILEGE_ANY)) {
    $adminUrl = UrlHelper::createUrl('admin/index');
    echo '<li><a href="' . Html::encode($adminUrl) . '" id="adminLink">Admin</a></li>';
}
echo '</ul>
            </div>
        </div>
    </div>';

echo '</header>';

echo '<div class="row logo">
<a href="' . Html::encode(UrlHelper::homeUrl()) . '" title="Startseite" class="homeLinkLogo">';
if ($controller->consultation && $controller->consultation->getSettings()->logoUrl != "") {
    $path     = parse_url($controller->consultation->getSettings()->logoUrl);
    $filename = basename($path['path']);
    $filename = substr($filename, 0, strrpos($filename, '.'));
    $filename = str_replace(
        ['_', 'ue', 'ae', 'oe', 'Ue', 'Oe', 'Ae'],
        [' ', 'ü', 'ä', 'ö', 'Ü' . 'Ö', 'Ä'],
        $filename
    );
    $logoUrl  = $controller->consultation->getSettings()->logoUrl;
    echo '<img src="' . Html::encode($logoUrl) . '" alt="' . Html::encode($filename) . '">';
} else {
    echo '<span class="logo_img"></span>';
}
echo '</a></div>';


echo $controller->showErrors();

if (is_array($layout->breadcrumbs)) {
    echo '<ol class="breadcrumb">';
    foreach ($layout->breadcrumbs as $link => $name) {
        if ($link == '' || is_null($link)) {
            echo '<li>' . Html::encode($name) . '</li>';
        } else {
            echo '<li>' . Html::a($name, $link) . '</li>';
        }
    }
    echo '</ol>';
}


/** @var string $content */
echo $content;

$legalLink   = UrlHelper::createUrl($controllerBase . 'legal');
$privacyLink = UrlHelper::createUrl($controllerBase . 'privacy');

echo '<div style="clear: both; padding-top: 15px;"></div>
<div class="footer_spacer"></div>
</div></div>';

echo '<footer class="footer">
        <div class="container">
            <a href="' . Html::encode($legalLink) . '" class="legal" id="legalLink">Impressum</a>
            <a href="' . Html::encode($privacyLink) . '" class="privacy" id="privacyLink">Datenschutz</a>

            <span class="version">
                Antragsgrün von <a href="https://www.hoessl.eu/">Tobias Hößl</a>,
                Version ' . Html::a(ANTRAGSGRUEN_VERSION, ANTRAGSGRUEN_HISTORY_URL) . '
            </span>
        </div>
    </footer>

    <script src="' . $resourceBase . 'js/bootstrap.js"></script>
    <script src="' . $resourceBase . 'js/bower/bootbox/bootbox.js"></script>
    <script src="' . $resourceBase . 'js/scrollintoview.js"></script>
    <script src="' . $resourceBase . 'js/jquery.isonscreen.js"></script>
    <script src="' . $resourceBase . 'js/bower/intl/dist/Intl.min.js"></script>
    <script src="' . $resourceBase . 'js/antragsgruen-de.js"></script>
    <script src="' . $resourceBase . 'js/antragsgruen.js"></script>
';

foreach ($layout->extraJs as $file) {
    echo '<script src="' . Html::encode($file) . '"></script>' . "\n";
}
foreach ($layout->onloadJs as $js) {
    echo '<script>' . $js . '</script>' . "\n";
}


$this->endBody();
echo '</body></html>';

$this->endPage();
