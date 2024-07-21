<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Motion $newMotion
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->robotsNoindex = true;
$layout->addBreadcrumb($newMotion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($newMotion));
$layout->addBreadcrumb(Yii::t('amend', 'merge_submitted'));

$title       = str_replace('%TITLE%', $newMotion->motionType->titleSingular, Yii::t('amend', 'merge_submitted_title'));
$this->title = $title . ': ' . $newMotion->getTitleWithPrefix();

echo '<h1>' . Html::encode($this->title) . '</h1>
<div class="content">
<div class="alert alert-success" role="alert">';
echo Yii::t('amend', 'merge_submitted_str');
echo '</div>';


echo Html::beginForm(UrlHelper::createMotionUrl($newMotion), 'post', ['id' => 'motionConfirmedForm']);
$msg = ($newMotion->isResolution() ? Yii::t('amend', 'merge_submitted_to_resolu') : Yii::t('amend', 'merge_submitted_to_motion'));
echo '<p class="btnRow"><button type="submit" class="btn btn-success">' . $msg . '</button></p>';
echo Html::endForm();

echo '</div>';
