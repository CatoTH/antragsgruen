<?php
use app\components\UrlHelper;
use app\models\AdminTodoItem;
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
} else {
    echo '<meta name="robots" content="index, follow">' . "\n";
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
    <script src="' . $layout->resourceUrl('js/jquery-1.12.4.min.js') . '"></script>
    <![endif]-->
    <!--[if gte IE 9]> -->
    <script src="' . $layout->resourceUrl('js/jquery-3.1.0.min.js') . '"></script>
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
        $homeUrl = UrlHelper::homeUrl();
        echo '<li class="active">' . Html::a(\Yii::t('base', 'Home'), $homeUrl, ['id' => 'homeLink']) . '</li>';
        if ($controller->consultation->hasHelpPage()) {
            $helpLink = UrlHelper::createUrl('consultation/help');
            echo '<li>' . Html::a(\Yii::t('base', 'Help'), $helpLink, ['id' => 'helpLink']) . '</li>';
        }
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
        $loginUrl   = UrlHelper::createUrl(['user/login', 'backUrl' => $backUrl]);
        $loginTitle = \Yii::t('base', 'menu_login');
        echo '<li>' . Html::a($loginTitle, $loginUrl, ['id' => 'loginLink', 'rel' => 'nofollow']) . '</li>';
    }
    if (User::getCurrentUser()) {
        $settingsTitle = \Yii::t('base', 'menu_account');
        $link          = Html::a($settingsTitle, UrlHelper::createUrl('user/myaccount'), ['id' => 'myAccountLink']);
        echo '<li>' . $link . '</li>';

        $logoutUrl   = UrlHelper::createUrl(['user/logout', 'backUrl' => \yii::$app->request->url]);
        $logoutTitle = \Yii::t('base', 'menu_logout');
        echo '<li>' . Html::a('Logout', $logoutUrl, ['id' => 'logoutLink']) . '</li>';
    }
    if (User::currentUserHasPrivilege($controller->consultation, User::PRIVILEGE_SCREENING)) {
        $adminUrl   = UrlHelper::createUrl('admin/motion/listall');
        $adminTitle = \Yii::t('base', 'menu_motion_list');
        echo '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'motionListLink']) . '</li>';
    }
    if (User::currentUserHasPrivilege($controller->consultation, User::PRIVILEGE_ANY)) {
        $todo = AdminTodoItem::getConsultationTodos($controller->consultation);
        if (count($todo) > 0) {
            $adminUrl   = UrlHelper::createUrl('admin/index/todo');
            $adminTitle = \Yii::t('base', 'menu_todo') . ' (' . count($todo) . ')';
            echo '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'adminTodo']) . '</li>';
        }

        $adminUrl   = UrlHelper::createUrl('admin/index');
        $adminTitle = \Yii::t('base', 'menu_admin');
        echo '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'adminLink']) . '</li>';
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

if ($controller->consultation) {
    $legalLink   = UrlHelper::createUrl('consultation/legal');
    $privacyLink = UrlHelper::createUrl('consultation/privacy');
} else {
    $legalLink   = UrlHelper::createUrl('manager/site-legal');
    $privacyLink = UrlHelper::createUrl('manager/site-privacy');
}

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

foreach ($layout->getJSFiles() as $jsFile) {
    echo '<script src="' . $jsFile . '"></script>' . "\n";
}
foreach ($layout->onloadJs as $js) {
    echo '<script>' . $js . '</script>' . "\n";
}

/** @var \app\models\settings\AntragsgruenApp $params */
$params = \Yii::$app->params;

$this->endBody();
echo '
<script type="application/ld+json">
    {
      "@context": "http://schema.org",
      "@type": "Organization",
      "url": "' . Html::encode($params->domainPlain) . '",
      "logo": "' . Html::encode($params->domainPlain) . 'img/logo.png"
    }
</script>
<script type="application/ld+json">
{
  "@context" : "http://schema.org",
  "@type" : "Organization",
  "name" : "Antragsgrün",
  "url" : "' . Html::encode($params->domainPlain) . '",
  "sameAs" : [
    "https://www.facebook.com/Antragsgruen",
    "https://twitter.com/Antragsgruen"
  ]
}
</script>
</body></html>';

$this->endPage();
