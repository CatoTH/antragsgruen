<?php

/**
 * @var $this yii\web\View
 * @var string $pageKey
 * @var string $saveUrl
 * @var bool $admin
 */

use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;

$consultation = \app\components\UrlHelper::getCurrentConsultation();
$pageData     = \app\components\MessageSource::getPageData($consultation, $pageKey);
$this->title  = $pageData->pageTitle;


$layout = $controller->layoutParams;
$layout->addBreadcrumb($pageData->breadcrumbTitle);

if ($admin) {
    $layout->loadCKEditor();
}

echo '<h1>' . Html::encode($pageData->pageTitle) . '</h1>';
echo '<div class="content contentPage">';

if ($admin) {
    echo '<a href="#" class="editCaller" style="float: right;">' . \Yii::t('base', 'edit') . '</a><br>';
    echo Html::beginForm($saveUrl, 'post', ['class' => 'contentEditForm']);
}

echo '<article class="textHolder" id="stdTextHolder">';
echo $pageData->text;
echo '</article>';

if ($admin) {
    echo '<div class="textSaver hidden">';
    echo '<button class="btn btn-primary submitBtn" type="button" data-save-url="' . Html::encode($saveUrl) . '">';
    echo \Yii::t('base', 'save') . '</button></div>';

    echo Html::endForm();
    $layout->addAMDModule('frontend/ContentPageEdit');
}

echo '</div>';
