<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var string $mode
 */

$this->title = \Yii::t('amend', $mode == 'create' ? 'amendment_create' : 'amendment_edit');

$layout->breadcrumbs[] = $this->title;
$layout->breadcrumbs[] = \Yii::t('amend', 'confirm_bread');


echo '<h1>' . \Yii::t('amend', 'amendment_submitted') . '</h1>';

echo '<div class="content">';
echo '<div class="alert alert-success" role="alert">';
if ($amendment->status == Amendment::STATUS_SUBMITTED_SCREENED) {
    echo \Yii::t('amend', 'confirmed_visible');
}
if ($amendment->status == Amendment::STATUS_SUBMITTED_UNSCREENED) {
    echo \Yii::t('amend', 'confirmed_screening');
}
if ($amendment->status == Amendment::STATUS_COLLECTING_SUPPORTERS) {
    $min = $amendment->getMyMotion()->motionType->getAmendmentSupportTypeClass()->getMinNumberOfSupporters();
    echo str_replace('%MIN%', $min, \Yii::t('amend', 'confirmed_support_phase'));
}

echo '</div>';

echo Html::beginForm(UrlHelper::createMotionUrl($amendment->getMyMotion()), 'post', ['id' => 'motionConfirmedForm']);

if ($amendment->status == Amendment::STATUS_COLLECTING_SUPPORTERS) {
    echo '<br><div class="alert alert-info promoUrl" role="alert">';
    echo Html::encode(UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment)));
    echo '</div>';
}

echo '<p class="btnRow"><button type="submit" class="btn btn-success">' . \Yii::t('amend', 'sidebar_back') .
    '</button></p>';
echo Html::endForm();
