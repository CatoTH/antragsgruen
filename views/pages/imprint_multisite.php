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
$pageData     = \app\components\MessageSource::getPageData($consultation, $pageKey);
$this->title  = $pageData->pageTitle;

/** @var \app\models\settings\AntragsgruenApp $params */
$params   = \Yii::$app->params;
$url      = parse_url(str_replace('<subdomain:[\w_-]+>', $consultation->site->subdomain, $params->domainSubdomain));
$currHost = $url['host'];

$layout = $controller->layoutParams;
$layout->addBreadcrumb($pageData->breadcrumbTitle);

if ($admin) {
    $layout->loadCKEditor();
}

echo '<h1>' . Html::encode($pageData->pageTitle) . '</h1>';
echo '<div class="content">' . \Yii::t('base', 'legal_multisite_hint') . '</div>';


echo '<h2 class="green">' . str_replace('%SITE%', $currHost, \Yii::t('base', 'legal_site_title')) . '</h2>';
echo '<div class="content contentPage">';

if ($admin) {
    echo '<a href="#" class="editCaller" style="float: right;">' . \Yii::t('base', 'edit') . '</a><br>';
    echo Html::beginForm($saveUrl, 'post');
}

echo '<article class="textHolder" id="stdTextHolder">';
echo $pageData->text;
echo '</article>';

if ($admin) {
    echo '<div class="textSaver hidden">';
    echo '<button class="btn btn-primary" type="button" data-save-url="' . Html::encode($saveUrl) . '">';
    echo \Yii::t('base', 'save') . '</button></div>';

    echo Html::endForm();
    $layout->addAMDModule('frontend/ContentPageEdit');
}

echo '</div>';

echo '<h2 class="green">' . \Yii::t('base', 'legal_base_title') . '</h2>
    <div class="content contentPage">';
echo \Yii::t('base', 'legal_base_intro') . '<br><br>';
/** @var ConsultationText $text */
$text = ConsultationText::findOne(['consultationId' => null, 'textId' => 'legal']);
if ($text) {
    echo $text->text;
}
echo '</div>';
