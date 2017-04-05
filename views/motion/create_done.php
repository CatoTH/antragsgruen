<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var string $mode
 * @var \app\controllers\Base $controller
 */

$this->title = Yii::t('motion', $mode == 'create' ? 'Start a Motion' : 'Edit Motion');

$controller = $this->context;
$controller->layoutParams->addBreadcrumb($this->title, UrlHelper::createMotionUrl($motion));

if ($motion->status == Motion::STATUS_COLLECTING_SUPPORTERS) {
    echo '<h1>' . Yii::t('motion', 'submitted_create') . '</h1>';
    $controller->layoutParams->addBreadcrumb(\Yii::t('motion', 'created_bread_create'));
} elseif ($motion->isInScreeningProcess()) {
    echo '<h1>' . Yii::t('motion', 'submitted_submit') . '</h1>';
    $controller->layoutParams->addBreadcrumb(\Yii::t('motion', 'created_bread_submit'));
} else {
    echo '<h1>' . Yii::t('motion', 'submitted_publish') . '</h1>';
    $controller->layoutParams->addBreadcrumb(\Yii::t('motion', 'created_bread_publish'));
}

echo '<div class="content">';
echo '<div class="alert alert-success" role="alert">';
if ($motion->status == Motion::STATUS_SUBMITTED_SCREENED) {
    echo \Yii::t('motion', 'confirmed_visible');
}
if ($motion->isInScreeningProcess()) {
    echo \Yii::t('motion', 'confirmed_screening');
}
if ($motion->status == Motion::STATUS_COLLECTING_SUPPORTERS) {
    $min = $motion->motionType->getMotionSupportTypeClass()->getMinNumberOfSupporters();
    echo str_replace('%MIN%', $min, \Yii::t('motion', 'confirmed_support_phase'));
}
echo '</div>';


echo Html::beginForm(UrlHelper::createUrl('consultation/index'), 'post', ['id' => 'motionConfirmedForm']);

if ($motion->status == Motion::STATUS_COLLECTING_SUPPORTERS) {
    $controller->layoutParams->addJS('npm/clipboard.min.js');
    $encodedUrl = Html::encode(UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion)));
    ?><br>
    <div class="alert alert-info promoUrl" role="alert" data-antragsgruen-widget="frontend/CopyUrlToClipboard">
        <div class="form-group">
            <div class="input-group">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button" data-clipboard-target="#urlSharing"
                            title="<?= \Yii::t('motion', 'copy_to_clipboard') ?>">
                        <span class="glyphicon glyphicon-copy"></span>
                    </button>
                </span>
                <input type="text" class="form-control" id="urlSharing" readonly value="<?= $encodedUrl ?>" title="URL">
            </div>
            <span class="glyphicon glyphicon-ok form-control-feedback" aria-hidden="true"></span>
            <span id="inputGroupSuccess1Status" class="sr-only">(success)</span>
        </div>
        <div class="hidden clipboard-done"><?= \Yii::t('motion', 'copy_to_clipboard_done') ?></div>
    </div>
    <?php
    if ($motion->motionType->policySupportMotions == \app\models\policies\IPolicy::POLICY_WURZELWERK) {
        echo '<div class="alert alert-info" role="alert">';
        echo \Yii::t('motion', 'confirmed_support_phase_ww');
        echo '</div>';
    }
}

echo '<p class="btnRow"><button type="submit" class="btn btn-success">' .
    \Yii::t('motion', 'back_start') . '</button></p>';
echo Html::endForm();
echo '</div>';
