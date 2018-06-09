<?php

/**
 * @var $this yii\web\View
 * @var string $pageKey
 * @var bool $admin
 */

use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;

$consultation = \app\components\UrlHelper::getCurrentConsultation();
$site         = ($consultation ? $consultation->site : null);
$pageData     = \app\models\db\ConsultationText::getPageData($site, $consultation, $pageKey);
$saveUrl      = $pageData->getSaveUrl();

$this->title = ($pageData->title ? $pageData->title : '');

$layout = $controller->layoutParams;
$layout->addBreadcrumb($pageData->breadcrumb ? $pageData->breadcrumb : '');

echo '<h1>' . Html::encode($pageData->title ? $pageData->title : '') . '</h1>';

if ($admin) {
    $layout->loadCKEditor();

    echo Html::beginForm($saveUrl, 'post', [
        'class'                 => 'contentEditForm',
        'data-upload-url'       => $pageData->getUploadUrl(),
        'data-image-browse-url' => $pageData->getImageBrowseUrl(),
        'data-antragsgruen-widget' => 'frontend/ContentPageEdit',
    ]);
}

echo '<div class="content contentPage">';

if ($admin) {
    echo '<a href="#" class="editCaller" style="float: right;">' . \Yii::t('base', 'edit') . '</a><br>';
}

echo '<article class="textHolder" id="stdTextHolder">';
echo $pageData->text;
echo '</article>';

if ($admin) {
    echo '<div class="textSaver hidden">';
    echo '<button class="btn btn-primary submitBtn" type="button" data-save-url="' . Html::encode($saveUrl) . '">';
    echo \Yii::t('base', 'save') . '</button></div>';
}

echo '</div>';

if ($admin) {
    echo Html::endForm();
}
