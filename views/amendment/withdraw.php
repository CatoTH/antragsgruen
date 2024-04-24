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
$motion     = $amendment->getMyMotion();

$layout->addBreadcrumb($motion->getMyMotionType()->titleSingular, UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb(Yii::t('amend', 'amendment'), UrlHelper::createAmendmentUrl($amendment));
$layout->addBreadcrumb(Yii::t('amend', 'withdraw_bread'));

$this->title = Yii::t('amend', 'withdraw') . ': ' . $amendment->getTitle();

echo '<h1>' . Yii::t('amend', 'withdraw') . ': ' . Html::encode($amendment->getTitle()) . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'content withdrawForm']);

echo '<div class="ask">' . Yii::t('amend', 'withdraw_confirm') . '</div>';
echo '<div class="stdEqualCols">';
echo '<div class="cancel"><button class="btn" name="cancel">' .
    Yii::t('amend', 'withdraw_no') . '</button></div>';
echo '<div class="withdraw"><button class="btn btn-danger" name="withdraw">' .
    Yii::t('amend', 'withdraw_yes') . '</button></div>';
echo '</div></div>';

echo Html::endForm();
