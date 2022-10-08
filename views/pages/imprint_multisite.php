<?php

/**
 * @var $this yii\web\View
 * @var string $pageKey
 * @var string $saveUrl
 * @var bool $admin
 */

use app\models\db\ConsultationText;
use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;

$consultation = \app\components\UrlHelper::getCurrentConsultation();
$pageData     = ConsultationText::getPageData(null, null, $pageKey);
$this->title  = $pageData->title;

/** @var \app\models\settings\AntragsgruenApp $params */
$params   = Yii::$app->params;
$url      = parse_url(str_replace('<subdomain:[\w_-]+>', $consultation->site->subdomain, $params->domainSubdomain));
$currHost = $url['host'];

$layout = $controller->layoutParams;
$layout->addBreadcrumb($pageData->breadcrumb);

if ($admin) {
    $layout->loadCKEditor();
}

echo '<h1>' . Html::encode($pageData->title) . '</h1>';

echo Html::beginForm($saveUrl, 'post', [
    'data-page-id'             => $pageData->id,
    'data-page-key'            => $pageData->textId,
    'data-upload-url'          => $pageData->getUploadUrl(),
    'data-image-browse-url'    => $pageData->getImageBrowseUrl(),
    'data-antragsgruen-widget' => 'frontend/ContentPageEdit',
    'data-text-selector'       => '#stdTextHolder',
    'data-save-selector'       => '.textSaver',
    'data-edit-selector'       => '.editCaller',
]);

echo '<div class="content">' . Yii::t('base', 'legal_multisite_hint') . '</div>';


echo '<h2 class="green">' . str_replace('%SITE%', $currHost, Yii::t('base', 'legal_site_title')) . '</h2>';
echo '<div class="content contentPage">';

if ($admin) {
    echo '<button type="button" class="btn btn-link editCaller">' . Yii::t('base', 'edit') . '</button><br>';
}

echo '<article class="textHolder" id="stdTextHolder">';
echo $pageData->text;
echo '</article>';

if ($admin) {
    echo '<div class="textSaver hidden">';
    echo '<button class="btn btn-primary" type="submit">';
    echo Yii::t('base', 'save') . '</button></div>';
}

echo '</div>';

echo Html::endForm();

echo '<h2 class="green">' . Yii::t('base', 'legal_base_title') . '</h2>
    <div class="content contentPage">';
echo Yii::t('base', 'legal_base_intro') . '<br><br>';
/** @var ConsultationText $text */
$text = ConsultationText::findOne(['consultationId' => null, 'textId' => 'legal']);
if ($text) {
    echo $text->text;
}
echo '</div>';
