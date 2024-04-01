<?php

use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var string $content
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout = $controller->layoutParams;
$params = \app\models\settings\AntragsgruenApp::getInstance();

$layout->registerPluginAssets($this, $controller);
if ($layout->mainCssFile === null) {
    $mainCssFile = $layout->resourceUrl('css/layout-classic.css');
} elseif (str_starts_with($layout->mainCssFile, 'layout-custom-')) {
    $mainCssHash = str_replace('layout-custom', '', $layout->mainCssFile);
    $mainCssFile = \app\components\UrlHelper::createUrl(['/pages/css', 'hash' => $mainCssHash]);
} elseif (str_starts_with($layout->mainCssFile, 'layout-plugin-')) {
    try {
        $mainCssFile = null;
        $layout->setPluginLayout($this);
    } catch (\app\models\exceptions\Internal $e) {
        $mainCssFile = $layout->resourceUrl('css/layout-classic.css');
    }
} else {
    $mainCssFile = $layout->resourceUrl('css/' . $layout->mainCssFile . '.css');
}

if (\app\components\DateTools::isDeadlineDebugModeActive($controller->consultation)) {
    $layout->loadDatepicker();
}

$bodyClasses = $layout->bodyCssClasses;
if ($layout->fullScreen) {
    $bodyClasses[] = 'fullscreen';
}

$title = $layout->formatTitle($this->title ?? '');

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
    <meta name="description" content="' . Html::encode(Yii::t('base', 'html_meta')) . '">' . "\n";
echo '<title>' . Html::encode($title) . '</title>' . "\n";
echo Html::csrfMetaTags();

if ($layout->isRobotsIndex($controller->action)) {
    echo '<meta name="robots" content="index, follow">' . "\n";
} else {
    echo '<meta name="robots" content="noindex, nofollow">' . "\n";
}

if ($layout->canonicalUrl) {
    echo '<link rel="canonical" href="' . Html::encode($layout->canonicalUrl) . '">' . "\n";
}
foreach ($layout->alternateLanuages as $lang => $url) {
    echo '<link rel="alternate" hreflang="' . Html::encode($lang) . '" href="' . Html::encode($url) . '">' . "\n";
}
foreach ($layout->feeds as $title => $url) {
    echo '<link rel="alternate" type="application/rss+xml" href="' . Html::encode($url) . '" ' .
        'title="' . Html::encode($title) . '">' . "\n";
}
foreach ($layout->extraCss as $file) {
    echo '<link rel="stylesheet" href="' . $layout->resourceUrl($file) . '">' . "\n";
}
foreach ($layout->inlineCss as $inlineCss) {
    echo '<style>' . $inlineCss . '</style>' . "\n";
}
$forbidRobots = ($controller->consultation && $controller->consultation->getSettings()->robotsPolicy === \app\models\settings\Consultation::ROBOTS_NONE);
if ($layout->ogImage !== null && $layout->ogImage !== '' && !$forbidRobots) {
    echo '<meta property="og:image" content="' . Html::encode($layout->ogImage) . '">' . "\n";
}

echo '<link rel="stylesheet" href="' . $mainCssFile . '">' . "\n";

echo '<script src="' . $layout->resourceUrl('npm/jquery.min.js') . '"></script>';

$consultation = $controller->consultation;
if ($layout->provideJwt && $params->jwtPrivateKey && $consultation) {
    $jwtConfig = \app\components\JwtCreator::getJwtConfigForCurrUser($consultation);
    echo '<meta name="user-jwt-config" content="' . Html::encode(json_encode($jwtConfig)) . '">' . "\n";
}
if (count($layout->connectLiveEvents) > 0 && $params->live && $consultation) {
    $liveConfig = \app\components\LiveTools::getJsConfig($consultation, $layout->connectLiveEvents);
    echo '<meta name="live-config" content="' . Html::encode(json_encode($liveConfig)) . '">' . "\n";
    echo '<script src="' . Html::encode($params->live['stompJsUri']) . '"></script>';
}
echo \app\models\layoutHooks\Layout::favicons();

$this->head();

echo \app\models\layoutHooks\Layout::endOfHead($controller->consultation);
echo '</head>';

if (defined('YII_ENV') && YII_ENV == 'test') {
    $bodyClasses[] = 'testing';
}

echo '<body' . (count($bodyClasses) > 0 ? ' class="' . implode(' ', $bodyClasses) . '"' : '') . '>';

$this->beginBody();

echo '<a href="#mainContent" id="gotoMainContent">' . Yii::t('base', 'goto_main_content') . '</a>';

echo '<div class="over_footer_wrapper">';

echo \app\models\layoutHooks\Layout::beforePage();
echo '<div id="page">';
echo \app\models\layoutHooks\Layout::beginPage();

echo \app\models\layoutHooks\Layout::logoRow();
echo $controller->showErrors();
echo \app\models\layoutHooks\Layout::beforeContent();

echo $content;

if (\app\components\DateTools::isDeadlineDebugModeActive($controller->consultation)) {
    echo $this->render('@app/views/consultation/_debug_time_bar', ['consultation' => $controller->consultation]);
}

echo '<div style="clear: both; padding-top: 15px;"></div>
<div class="footer_spacer"></div>
</div></div>';

echo \app\models\layoutHooks\Layout::endPage();

if (count($layout->connectLiveEvents) && $params->live && $consultation) {
    echo '<script src="' . $layout->resourceUrl('js/antragsgruen-live-events.js') . '"></script>';
}

foreach ($layout->getJSFiles() as $jsFile) {
    echo '<script src="' . $jsFile . '"></script>' . "\n";
}
foreach ($layout->onloadJs as $js) {
    echo '<script>' . $js . '</script>' . "\n";
}
echo $layout->getAMDClasses();
echo $layout->getAMDLoader();

foreach ($layout->vueTemplates as $vueTemplate) {
    echo $this->render($vueTemplate);
}

$this->endBody();
echo '
<script type="application/ld+json">
    {
      "@context": "http://schema.org",
      "@type": "Organization",
      "url": "' . Html::encode($params->domainPlain) . '",
      "logo": "' . Html::encode($params->getAbsoluteResourceBase()) . 'img/logo.png"
    }
</script>
<script type="application/ld+json">
{
  "@context" : "http://schema.org",
  "@type" : "Organization",
  "name" : "' . Yii::t('export', 'default_creator') . '",
  "url" : "' . Html::encode($params->domainPlain) . '",
  "sameAs" : [
    "https://www.facebook.com/Antragsgruen",
    "https://twitter.com/Antragsgruen"
  ]
}
</script>
</body></html>';

$this->endPage();
