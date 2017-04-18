<?php
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
if ($layout->robotsNoindex || \app\models\settings\AntragsgruenApp::getInstance()->mode == 'sandbox') {
    echo '<meta name="robots" content="noindex, nofollow">' . "\n";
} else {
    echo '<meta name="robots" content="index, follow">' . "\n";
}

if ($layout->canonicalUrl) {
    echo '<link rel="canonical" href="' . Html::encode($layout->canonicalUrl) . '">' . "\n";
}
foreach ($layout->alternateLanuages as $lang => $url) {
    echo '<link rel="alternate" hreflang="' . Html::encode($lang) . '" href="' . Html::encode($url) . '">' . "\n";
}
foreach ($layout->extraCss as $file) {
    echo '<link rel="stylesheet" href="' . $layout->resourceUrl($file) . '">' . "\n";
}

echo '<link rel="stylesheet" href="' . $layout->resourceUrl('css/' . $layout->mainCssFile . '.css') . '">' . "\n";

echo '<script src="' . $layout->resourceUrl('npm/jquery.min.js') . '"></script>
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

if (defined('YII_ENV') && YII_ENV == 'test') {
    $bodyClasses[] = 'testing';
}

echo '<body ' . (count($bodyClasses) > 0 ? 'class="' . implode(' ', $bodyClasses) . '"' : '') . '>';

$modernizr = file_get_contents(\Yii::$app->basePath . '/web/js/modernizr.js');
echo '<script>' . $modernizr . '</script>' . "\n";

$this->beginBody();

echo '<div class="over_footer_wrapper">';

echo $layout->hooks->beforePage();
echo '<div class="container" id="page">';
echo $layout->hooks->beginPage();

echo $layout->hooks->logoRow();
echo $controller->showErrors();
echo $layout->hooks->beforeContent();

/** @var string $content */
echo $content;


echo '<div style="clear: both; padding-top: 15px;"></div>
<div class="footer_spacer"></div>
</div></div>';

echo $layout->hooks->endPage();


foreach ($layout->getJSFiles() as $jsFile) {
    echo '<script src="' . $jsFile . '"></script>' . "\n";
}
foreach ($layout->onloadJs as $js) {
    echo '<script>' . $js . '</script>' . "\n";
}

echo $layout->getAMDLoader();
echo $layout->getAMDClasses();

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
