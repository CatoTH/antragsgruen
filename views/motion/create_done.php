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
$controller->layoutParams->addBreadcrumb(\Yii::t('motion', 'created_bread'));


echo '<h1>' . Yii::t('motion', 'Motion submitted') . '</h1>';

echo '<div class="content">';
echo '<div class="alert alert-success" role="alert">';
if ($motion->status == Motion::STATUS_SUBMITTED_SCREENED) {
    echo \Yii::t('motion', 'confirmed_visible');
}
if ($motion->status == Motion::STATUS_SUBMITTED_UNSCREENED) {
    echo \Yii::t('motion', 'confirmed_screening');
}
if ($motion->status == Motion::STATUS_COLLECTING_SUPPORTERS) {
    $min = $motion->motionType->getMotionSupportTypeClass()->getMinNumberOfSupporters();
    echo str_replace('%MIN%', $min, \Yii::t('motion', 'confirmed_support_phase'));
}
echo '</div>';


echo Html::beginForm(UrlHelper::createUrl('consultation/index'), 'post', ['id' => 'motionConfirmedForm']);

if ($motion->status == Motion::STATUS_COLLECTING_SUPPORTERS) {
    echo '<br><div class="alert alert-info promoUrl" role="alert">';
    echo Html::encode(UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion)));
    echo '</div>';
}

echo '<p class="btnRow"><button type="submit" class="btn btn-success">' .
    \Yii::t('motion', 'back_start') . '</button></p>';
echo Html::endForm();
echo '</div>';
