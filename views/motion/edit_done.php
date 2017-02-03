<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = \Yii::t('motion', 'edit_done');

$layout->robotsNoindex = true;
if ($motion->titlePrefix) {
    $layout->addBreadcrumb($motion->titlePrefix, UrlHelper::createMotionUrl($motion));
} else {
    $layout->addBreadcrumb($motion->motionType->titleSingular, UrlHelper::createMotionUrl($motion));
}
$layout->addBreadcrumb(\Yii::t('motion', 'edit_bread'));


echo '<h1>' . \Yii::t('motion', 'edit_done') . '</h1>';

echo '<div class="content">';
echo '<div class="alert alert-success" role="alert">';
echo \Yii::t('motion', 'edit_done_msg');
echo '</div>';

echo Html::beginForm(UrlHelper::createMotionUrl($motion), 'post', ['id' => 'motionConfirmedForm']);
echo '<p class="btnRow"><button type="submit" class="btn btn-success">' . \Yii::t('motion', 'back_to_motion') .
    '</button></p>';
echo Html::endForm();
