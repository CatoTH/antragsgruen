<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Motion $motion
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('motion', 'edit_done');

$motionType = $motion->getMyMotionType();
$layout->robotsNoindex = true;
if ($motion->getFormattedTitlePrefix()) {
    $layout->addBreadcrumb($motion->getFormattedTitlePrefix(), UrlHelper::createMotionUrl($motion));
} else {
    $layout->addBreadcrumb($motionType->titleSingular, UrlHelper::createMotionUrl($motion));
}
$layout->addBreadcrumb(Yii::t('motion', 'edit_bread'));


echo '<h1>' . Yii::t('motion', 'edit_done') . '</h1>';

echo '<div class="content">';
echo '<div class="alert alert-success" role="alert">';
echo Yii::t('motion', 'edit_done_msg');
echo '</div>';

$backMsg = $motionType->getConsultationTextWithFallback('motion', 'back_to_motion');
echo Html::beginForm(UrlHelper::createMotionUrl($motion), 'post', ['id' => 'motionConfirmedForm']);
echo '<p class="btnRow"><button type="submit" class="btn btn-success">' . $backMsg . '</button></p>';
echo Html::endForm();
