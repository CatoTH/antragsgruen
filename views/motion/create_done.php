<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Motion $motion
 * @var string $mode
 * @var \app\controllers\Base $controller
 */

$typeName = $motion->getMyMotionType()->titleSingular;
if ($mode === 'create') {
    $this->title = $typeName;
} else {
    $this->title = str_replace('%TYPE%', $typeName, Yii::t('motion', 'motion_edit'));
}

$controller = $this->context;
$controller->layoutParams->addBreadcrumb($this->title, UrlHelper::createMotionUrl($motion));

if ($motion->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
    echo '<h1>' . str_replace('%TITLE%', $typeName, Yii::t('motion', 'submitted_create')) . '</h1>';
    $controller->layoutParams->addBreadcrumb(Yii::t('motion', 'created_bread_create'));
} elseif ($motion->isInScreeningProcess()) {
    echo '<h1>' . str_replace('%TITLE%', $typeName, Yii::t('motion', 'submitted_submit')) . '</h1>';
    $controller->layoutParams->addBreadcrumb(Yii::t('motion', 'created_bread_submit'));
} else {
    echo '<h1>' . str_replace('%TITLE%', $typeName, Yii::t('motion', 'submitted_publish')) . '</h1>';
    $controller->layoutParams->addBreadcrumb(Yii::t('motion', 'created_bread_publish'));
}

echo '<div class="content">';
echo '<div class="alert alert-success" role="alert">';
if ($motion->status === Motion::STATUS_SUBMITTED_SCREENED) {
    echo Yii::t('motion', 'confirmed_visible');
}
if ($motion->isInScreeningProcess()) {
    echo Yii::t('motion', 'confirmed_screening');
}
if ($motion->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
    $supportType   = $motion->getMyMotionType()->getMotionSupportTypeClass();
    $min           = $supportType->getSettingsObj()->minSupporters;
    $msgTpl        = $motion->getMyMotionType()->getConsultationTextWithFallback('motion', 'confirmed_support_phase');
    $msg           = str_replace(['%TITLE%', '%MIN%'], [$typeName, $min], $msgTpl);
    $requirement   = '';
    $missingFemale = $motion->getMissingSupporterCountByGender($supportType, 'female');
    if ($missingFemale > 0) {
        $requirementTpl = $motion->getMyMotionType()->getConsultationTextWithFallback('motion', 'confirmed_support_phase_addfemale');
        $requirement = str_replace('%MIN%', $missingFemale, $requirementTpl);
    }
    $msg = str_replace('%ADD_REQUIREMENT%', $requirement, $msg);

    echo $msg;
}
echo '</div>';


echo Html::beginForm(UrlHelper::homeUrl(), 'post', ['id' => 'motionConfirmedForm']);

if ($motion->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
    $controller->layoutParams->addJS('npm/clipboard.min.js');
    $encodedUrl = Html::encode(UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion)));
    ?><br>
    <div class="alert alert-info promoUrl" role="alert" data-antragsgruen-widget="frontend/CopyUrlToClipboard">
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
            <span id="inputGroupSuccess1Status" class="sr-only">(<?= Yii::t('base', 'aria_success') ?>)</span>
        </div>
        <div class="hidden clipboard-done"><?= Yii::t('motion', 'copy_to_clipboard_done') ?></div>
    </div>
    <?php
    if (is_a($motion->getMyMotionType()->getMotionSupportPolicy(), \app\models\policies\GruenesNetz::class)) {
        echo '<div class="alert alert-info">';
        echo $motion->getMyMotionType()->getConsultationTextWithFallback('motion', 'confirmed_support_phase_ww');
        echo '</div>';
    }
}

echo '<p class="btnRow"><button type="submit" class="btn btn-success">' .
     Yii::t('motion', 'back_start') . '</button></p>';
echo Html::endForm();
echo '</div>';
