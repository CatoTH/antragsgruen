<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\ConsultationMotionType;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Amendment $amendment
 * @var string $mode
 * @var \app\controllers\Base $controller
 */

$this->title = Yii::t('amend', $mode == 'create' ? 'amendment_create' : 'amendment_edit');
$controller  = $this->context;
$motion      = $amendment->getMyMotion();
$motionType  = $amendment->getMyMotionType();
$layout      = $controller->layoutParams;

if (!$motion->getMyMotionType()->amendmentsOnly) {
    $layout->addBreadcrumb($motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($motion));
}
if ($amendment->amendingAmendmentId) {
    $amendedAmendment = $amendment->amendedAmendment;
    $layout->addBreadcrumb($amendedAmendment->getFormattedTitlePrefix(), UrlHelper::createAmendmentUrl($amendedAmendment));
}
$layout->addBreadcrumb($amendment->getFormattedTitlePrefix() ?? Yii::t('amend', 'amendment'), UrlHelper::createAmendmentUrl($amendment));

if ($amendment->status == Amendment::STATUS_COLLECTING_SUPPORTERS) {
    echo '<h1>' . Yii::t('amend', 'submitted_create') . '</h1>';
    $controller->layoutParams->addBreadcrumb(Yii::t('amend', 'created_bread_create'));
} elseif ($amendment->isInScreeningProcess()) {
    echo '<h1>' . Yii::t('amend', 'submitted_submit') . '</h1>';
    $controller->layoutParams->addBreadcrumb(Yii::t('amend', 'created_bread_submit'));
} else {
    echo '<h1>' . Yii::t('amend', 'submitted_publish') . '</h1>';
    $controller->layoutParams->addBreadcrumb(Yii::t('amend', 'created_bread_publish'));
}

echo '<div class="content">';
echo '<div class="alert alert-success" role="alert">';
if ($amendment->status == Amendment::STATUS_SUBMITTED_SCREENED) {
    echo Yii::t('amend', 'confirmed_visible');
}
if ($amendment->isInScreeningProcess()) {
    echo Yii::t('amend', 'confirmed_screening');
}
if ($amendment->status === Amendment::STATUS_COLLECTING_SUPPORTERS) {
    $supportType   = $motionType->getAmendmentSupportTypeClass();
    $min           = $supportType->getSettingsObj()->minSupporters;
    $msgTpl        = $motionType->getConsultationTextWithFallback('amend', 'confirmed_support_phase');
    $msg           = str_replace('%MIN%', $min, $msgTpl);
    $requirement   = '';
    $missingFemale = $amendment->getMissingSupporterCountByGender($supportType, 'female');
    if ($missingFemale > 0) {
        $requirementTpl = $motionType->getConsultationTextWithFallback('amend', 'confirmed_support_phase_addfemale');
        $requirement = str_replace('%MIN%', $missingFemale, $requirementTpl);
    }
    $msg = str_replace('%ADD_REQUIREMENT%', $requirement, $msg);

    echo $msg;
}

echo '</div>';

if ($motionType->amendmentsOnly) {
    $backUrl = UrlHelper::homeUrl();
} else {
    $backUrl = UrlHelper::createMotionUrl($amendment->getMyMotion());
    if ($amendment->status == Amendment::STATUS_SUBMITTED_SCREENED &&
        in_array($motionType->amendmentMultipleParagraphs, [ConsultationMotionType::AMEND_PARAGRAPHS_SINGLE_PARAGRAPH, ConsultationMotionType::AMEND_PARAGRAPHS_SINGLE_CHANGE])) {
        $backUrl .= '#amendment' . $amendment->id;
    }
}
echo Html::beginForm($backUrl, 'post', ['id' => 'motionConfirmedForm']);

if ($amendment->status === Amendment::STATUS_COLLECTING_SUPPORTERS) {
    $controller->layoutParams->addJS('npm/clipboard.min.js');
    $encodedUrl = Html::encode(UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment)));
    ?><br>
    <div class="alert alert-info promoUrl" data-antragsgruen-widget="frontend/CopyUrlToClipboard">
        <div class="form-group">
            <div class="input-group">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button" data-clipboard-target="#urlSharing"
                            title="<?= Yii::t('motion', 'copy_to_clipboard') ?>">
                        <span class="glyphicon glyphicon-copy" aria-hidden="true"></span>
                        <span class="sr-only"><?= Yii::t('motion', 'copy_to_clipboard') ?></span>
                    </button>
                </span>
                <input type="text" class="form-control" id="urlSharing" readonly value="<?= $encodedUrl ?>" title="URL">
            </div>
            <span class="glyphicon glyphicon-ok form-control-feedback" aria-hidden="true"></span>
            <span id="inputGroupSuccess1Status" class="sr-only"><?= Yii::t('base', 'aria_success') ?></span>
        </div>
        <div class="hidden clipboard-done"><?= Yii::t('motion', 'copy_to_clipboard_done') ?></div>
    </div>
    <?php
    if (is_a($motionType->getMotionSupportPolicy(), \app\models\policies\GruenesNetz::class)) {
        echo '<div class="alert alert-info">';
        echo Yii::t('amend', 'confirmed_support_phase_ww');
        echo '</div>';
    }
}

echo '<p class="btnRow"><button type="submit" class="btn btn-success">';
if ($motionType->amendmentsOnly) {
    echo Yii::t('motion', 'back_start');
} else {
    echo Yii::t('amend', 'sidebar_back');
}
echo '</button></p>';
echo Html::endForm();
