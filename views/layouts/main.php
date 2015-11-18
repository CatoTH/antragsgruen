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
if (defined('YII_FROM_ROOTDIR') && YII_FROM_ROOTDIR === true) {
    $resourceBase .= 'web/';
}

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

$this->beginPage();


echo '<!DOCTYPE HTML>
<html lang="' . Html::encode($layout->getHTMLLanguageCode()) . '"';
if ($controller->consultation) {
    echo ' data-lang-variant="' . Html::encode($controller->consultation->wordingBase) . '"';
}
echo '>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="' . Html::encode(\Yii::t('base', 'html_meta')) . '">' . "\n";
echo '<title>' . Html::encode($title) . '</title>' . "\n";
echo Html::csrfMetaTags();

if ($controller->consultation && $controller->consultation->getSettings()->logoUrlFB != '') {
    echo '<link rel="image_src" href="' . Html::encode($controller->consultation->getSettings()->logoUrlFB) . '">';
}
if ($layout->robotsNoindex) {
    echo '<meta name="robots" content="noindex, nofollow">' . "\n";
}

echo '<!--[if lt IE 9]>
    <script src="' . $layout->resourceUrl('js/bower/html5shiv/dist/html5shiv.min.js') . '"></script>
    <![endif]-->
    <!--[if lt IE 8]>
    <link rel="stylesheet" href="' . $layout->resourceUrl('css/antragsgruen-ie7.css') . '">
    <![endif]-->
';

foreach ($layout->extraCss as $file) {
    echo '<link rel="stylesheet" href="' . $layout->resourceUrl($file) . '">' . "\n";
}

echo '<link rel="stylesheet" href="' . $layout->resourceUrl('css/' . $layout->mainCssFile . '.css') . '">' . "\n";

echo '<!--[if lt IE 9]>
    <script src="' . $layout->resourceUrl('js/jquery-1.11.3.min.js') . '"></script>
    <![endif]-->
    <!--[if gte IE 9]><!-->
    <script src="' . $layout->resourceUrl('js/bower/jquery/dist/jquery.min.js') . '"></script>
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

echo '<body ' . (count($bodyClasses) > 0 ? 'class="' . implode(' ', $bodyClasses) . '"' : '') . '>';

echo '<script src="' . $layout->resourceUrl('js/modernizr.js') . '"></script>';

$this->beginBody();

echo '<div class="over_footer_wrapper">';
echo '<div class="container" id="page">';
echo '<header id="mainmenu">';
echo '<div class="navbar">
        <div class="navbar-inner">
            <div class="container">';

echo '<ul class="nav navbar-nav">';

if (!defined('INSTALLING_MODE') || INSTALLING_MODE !== true) {
    if ($controller->consultation) {
        $homeUrl = UrlHelper::createUrl('consultation/index');
        echo '<li class="active">' . Html::a(\Yii::t('base', 'Home'), $homeUrl) . '</li>';
        $helpLink = UrlHelper::createUrl('consultation/help');
        echo '<li>' . Html::a(\Yii::t('base', 'Help'), $helpLink, ['id' => 'helpLink']) . '</li>';
    } else {
        $startLink = UrlHelper::createUrl('manager/index');
        echo '<li class="active">' . Html::a(\Yii::t('base', 'Home'), $startLink) . '</li>';
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
        $link = Html::a(\Yii::t('base', 'Settings'), UrlHelper::createUrl('user/myaccount'), ['id' => 'myAccountLink']);
        echo '<li>' . $link . '</li>';

        $logoutUrl = UrlHelper::createUrl(['user/logout', 'backUrl' => \yii::$app->request->url]);
        echo '<li>' . Html::a('Logout', $logoutUrl, ['id' => 'logoutLink']) . '</li>';
    }
    if (User::currentUserHasPrivilege($controller->consultation, User::PRIVILEGE_ANY)) {
        $adminUrl = UrlHelper::createUrl('admin/index');
        echo '<li><a href="' . Html::encode($adminUrl) . '" id="adminLink">Admin</a></li>';
    }
}
echo '</ul>
            </div>
        </div>
    </div>';

echo '</header>';

echo '<div class="row logo">
<a href="' . Html::encode(UrlHelper::homeUrl()) . '" class="homeLinkLogo text-hide">' . \Yii::t('base', 'Home');
if ($controller->consultation && $controller->consultation->getSettings()->logoUrl != '') {
    $path     = parse_url($controller->consultation->getSettings()->logoUrl);
    $filename = basename($path['path']);
    $filename = substr($filename, 0, strrpos($filename, '.'));
    $filename = str_replace(
        ['_', 'ue', 'ae', 'oe', 'Ue', 'Oe', 'Ae'],
        [' ', 'ü', 'ä', 'ö', 'Ü' . 'Ö', 'Ä'],
        $filename
    );
    $logoUrl  = $controller->consultation->getSettings()->logoUrl;
    if (!isset($path['host']) && $logoUrl[0] != '/') {
        $logoUrl = $resourceBase . $logoUrl;
    }
    echo '<img src="' . Html::encode($logoUrl) . '" alt="' . Html::encode($filename) . '">';
} else {
    echo '<span class="logoImg"></span>';
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
            <a href="' . Html::encode($legalLink) . '" class="legal" id="legalLink">' .
    \Yii::t('base', 'imprint') . '</a>
            <a href="' . Html::encode($privacyLink) . '" class="privacy" id="privacyLink">' .
    \Yii::t('base', 'privacy_statement') . '</a>

            <span class="version">';
if (\Yii::$app->language == 'de') {
    echo 'Antragsgrün von <a href="https://www.hoessl.eu/">Tobias Hößl</a>,
        Version ' . Html::a(ANTRAGSGRUEN_VERSION, ANTRAGSGRUEN_HISTORY_URL);
} else {
    echo 'Antragsgrün by <a href="https://www.hoessl.eu/">Tobias Hößl</a>,
        Version ' . Html::a(ANTRAGSGRUEN_VERSION, ANTRAGSGRUEN_HISTORY_URL);
}

echo '</span>
        </div>
    </footer>';

$jsLang = $layout->getJSLanguageCode();
if (defined('YII_DEBUG') && YII_DEBUG) {
    echo '<script src="' . $layout->resourceUrl('js/bootstrap.js') . '"></script>
    <script src="' . $layout->resourceUrl('js/bower/bootbox/bootbox.js') . '"></script>
    <script src="' . $layout->resourceUrl('js/scrollintoview.js') . '"></script>
    <script src="' . $layout->resourceUrl('js/jquery.isonscreen.js') . '"></script>
    <script src="' . $layout->resourceUrl('js/bower/intl/dist/Intl.min.js') . '"></script>
    <script src="' . $layout->resourceUrl('js/antragsgruen.js') . '"></script>
    <script src="' . $layout->resourceUrl('js/antragsgruen-' . $jsLang . '.js') . '"></script>';
} else {
    echo '<script src="' . $layout->resourceUrl('js/build/antragsgruen.min.js') . '"></script>
    <script src="' . $layout->resourceUrl('js/build/antragsgruen-' . $jsLang . '.min.js') . '"></script>';
}

foreach ($layout->extraJs as $file) {
    echo '<script src="' . $layout->resourceUrl($file) . '"></script>' . "\n";
}
foreach ($layout->onloadJs as $js) {
    echo '<script>' . $js . '</script>' . "\n";
}


$this->endBody();
echo '</body></html>';

$this->endPage();
