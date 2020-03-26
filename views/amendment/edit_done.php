<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Amendment $amendment
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('amend', 'edit_done');

$layout->robotsNoindex = true;
$layout->addBreadcrumb(Yii::t('amend', 'amendment'));
$layout->addBreadcrumb(Yii::t('amend', 'edit_bread'));


echo '<h1>' . Yii::t('amend', 'edit_done') . '</h1>';

echo '<div class="content">';
echo '<div class="alert alert-success" role="alert">';
echo Yii::t('amend', 'edit_done_msg');
echo '</div>';

echo Html::beginForm(UrlHelper::createAmendmentUrl($amendment), 'post', ['id' => 'motionConfirmedForm']);
echo '<p class="btnRow"><button type="submit" class="btn btn-success">' . Yii::t('amend', 'back_to_amend') .
    '</button></p>';
echo Html::endForm();
