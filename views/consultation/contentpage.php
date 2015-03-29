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

$pageData    = $controller->consultation->getPageData($pageKey);
$this->title = $pageData->pageTitle;


$layout     = $controller->layoutParams;
$layout->addBreadcrumb($pageData->breadcrumbTitle);

if ($admin) {
    $layout->addJS('/js/ckeditor/ckeditor.js');
}

echo '<h1>' . Html::encode($pageData->pageTitle) . '</h1>';
echo '<div class="content contentPage">';

if ($admin) {
    echo '<a href="#" class="editCaller" style="float: right;">Bearbeiten</a><br>';
    echo Html::beginForm($saveUrl, 'post');
}

echo '<article class="textHolder" id="stdTextHolder">';
echo $pageData->text;
echo '</article>';

if ($admin) {
    echo '<div class="textSaver" style="display: none;">';
    echo '<button class="btn btn-primary" type="button" data-save-url="' . Html::encode($saveUrl) . '">';
    echo 'Speichern</button></div>';

    echo Html::endForm();
    $layout->addOnLoadJS('$.Antragsgruen.contentPageEdit();');
}

echo '</div>';
