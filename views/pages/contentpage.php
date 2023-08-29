<?php

/**
 * @var $this yii\web\View
 * @var string $pageKey
 * @var bool $admin
 */

use app\components\UrlHelper;
use app\models\db\{ConsultationText, User};
use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;

$consultation = UrlHelper::getCurrentConsultation();
$site         = ($consultation ? $consultation->site : null);
$pageData     = ConsultationText::getPageData($site, $consultation, $pageKey);
$saveUrl      = $pageData->getSaveUrl();

$this->title = $pageData->title ?: $pageData->textId;

$layout = $controller->layoutParams;
if (!in_array($controller->action->id, ['home', 'index'])) {
    $layout->addBreadcrumb($pageData->breadcrumb ?: $pageData->textId);
} else {
    $layout->breadcrumbs = [];
}

if (User::getCurrentUser() && $pageData->isCustomPage()) {
    $layout->loadVue();
    $layout->addFullscreenTemplates();
    $fullscreenInitData = json_encode([
        'consultation_url' => UrlHelper::createUrl(['/consultation/rest']),
        'init_page' => 'page-' . $pageData->id,
        'init_content_url' => UrlHelper::absolutizeLink($pageData->getJsonUrl()),
    ]);
    $fullscreenButton = '<button type="button" title="' . Yii::t('motion', 'fullscreen') . '" class="btn btn-link btnFullscreen"
        data-antragsgruen-widget="frontend/FullscreenToggle" data-vue-element="fullscreen-projector" data-vue-initdata="' . Html::encode($fullscreenInitData) . '">
        <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
        <span class="sr-only">' . Yii::t('motion', 'fullscreen') . '</span>
    </button>';
} else {
    $fullscreenButton = '';
}

echo '<div class="primaryHeader"><h1 class="pageTitle">' . Html::encode($pageData->title ?: $pageData->textId) . '</h1>' . $fullscreenButton . '</div>';

if ($admin) {
    $layout->loadCKEditor();

    echo Html::beginForm($saveUrl, 'post', [
        'class'                    => 'contentEditForm',
        'data-upload-url'          => $pageData->getUploadUrl(),
        'data-image-browse-url'    => $pageData->getImageBrowseUrl(),
        'data-antragsgruen-widget' => 'frontend/ContentPageEdit',
        'data-text-selector'       => '#stdTextHolder',
        'data-save-selector'       => '.textSaver',
        'data-edit-selector'       => '.editCaller',
    ]);

    if (!in_array($pageData->textId, array_keys(ConsultationText::getDefaultPages()))) {
        ?>
        <section class="contentSettingsToolbar toolbarBelowTitle row form-inline hidden">
            <div class="col-md-4 textfield">
                <div class="form-group">
                    <label for="contentUrl"><?= Yii::t('pages', 'settings_url') ?>:</label>
                    <input type="text" class="form-control" name="url" value="<?= Html::encode($pageData->textId) ?>"
                           required id="contentUrl">
                </div>
            </div>
            <div class="col-md-4 textfield">
                <div class="form-group">
                    <label for="contentTitle"><?= Yii::t('pages', 'settings_title') ?>:</label>
                    <input type="text" class="form-control" name="title" value="<?= Html::encode($pageData->title) ?>"
                           required id="contentTitle" maxlength="30">
                </div>
            </div>
            <div class="col-md-4 options">
                <label>
                    <?= Html::checkbox('allConsultations', ($pageData->consultationId === null)) ?>
                    <?= Yii::t('pages', 'settings_allcons') ?>
                </label>
                <label>
                    <?= Html::checkbox('inMenu', ($pageData->menuPosition !== null)) ?>
                    <?= Yii::t('pages', 'settings_inmenu') ?>
                </label>
            </div>
        </section>
        <?php
    }

    echo Html::endForm();
}


$contentMain = '<div class="content contentPage">';

if ($admin) {
    $contentMain .= '<button type="button" class="btn btn-link editCaller">' . Yii::t('base', 'edit') . '</button><br>';
}

$contentMain .= '<article class="textHolder" id="stdTextHolder">';
$contentMain .= $pageData->text;
$contentMain .= '</article>';

if ($admin) {
    $contentMain .= '<div class="textSaver hidden">';
    $contentMain .= '<button class="btn btn-primary submitBtn" type="submit">';
    $contentMain .= Yii::t('base', 'save') . '</button></div>';
}

$contentMain .= '</div>';

$contentMain = \app\models\layoutHooks\Layout::getContentPageContent($pageData, $admin, $contentMain);

echo $contentMain;

if ($admin) {
    $deleteUrl = UrlHelper::createUrl(['pages/delete-page', 'pageSlug' => $pageData->textId]);
    echo Html::beginForm($deleteUrl, 'post', ['class' => 'deletePageForm']);
    echo '<input type="hidden" name="delete" value="delete">';
    echo '<button type="submit" class="btn btn-link btn-danger pull-right">';
    echo '<span class="glyphicon glyphicon-trash" aria-hidden="true"></span> ' . Yii::t('pages', 'settings_delete');
    echo '</button>';
    echo Html::endForm();
}
