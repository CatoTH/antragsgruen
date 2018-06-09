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

echo '<h1 class="pageTitle">' . Html::encode($pageData->title ? $pageData->title : '') . '</h1>';

if ($admin) {
    $layout->loadCKEditor();

    echo Html::beginForm($saveUrl, 'post', [
        'class'                    => 'contentEditForm',
        'data-upload-url'          => $pageData->getUploadUrl(),
        'data-image-browse-url'    => $pageData->getImageBrowseUrl(),
        'data-antragsgruen-widget' => 'frontend/ContentPageEdit',
    ]);
    ?>
    <section class="contentSettingsToolbar toolbarBelowTitle row form-inline hidden">
        <div class="col-md-4 textfield">
            <div class="form-group">
                <label for="contentUrl"><?= \Yii::t('pages', 'settings_url') ?>:</label>
                <input type="text" class="form-control" name="url" value="<?= Html::encode($pageData->textId) ?>"
                       required id="contentUrl">
            </div>
        </div>
        <div class="col-md-4 textfield">
            <div class="form-group">
                <label for="contentTitle"><?= \Yii::t('pages', 'settings_title') ?>:</label>
                <input type="text" class="form-control" name="title" value="<?= Html::encode($pageData->title) ?>"
                       required id="contentTitle" maxlength="30">
            </div>
        </div>
        <div class="col-md-4 options">
            <label>
                <?= Html::checkbox('allConsultations', ($pageData->consultationId === null)) ?>
                <?= \Yii::t('pages', 'settings_allcons') ?>
            </label>
            <label>
                <?= Html::checkbox('inMenu', ($pageData->menuPosition !== null)) ?>
                <?= \Yii::t('pages', 'settings_inmenu') ?>
            </label>
        </div>
    </section>
    <?php
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
    echo '<button class="btn btn-primary submitBtn" type="submit">';
    echo \Yii::t('base', 'save') . '</button></div>';
}

echo '</div>';

if ($admin) {
    echo Html::endForm();
}
