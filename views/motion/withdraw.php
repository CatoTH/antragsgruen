<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use app\components\Html;

/**
 * @var \app\components\view\View $this
 * @var \app\models\settings\Layout $layout
 * @var Motion $motion
 */


$layout->addBreadcrumb($motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb(Yii::t('motion', 'withdraw_bread'));

$this->title = Yii::t('motion', 'withdraw') . ': ' . $motion->getTitleWithPrefix();

echo '<h1>' . Yii::t('motion', 'withdraw') . ': ' . $motion->getEncodedTitleWithPrefix() . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'content withdrawForm']);

echo '<div class="ask">' . Yii::t('motion', 'withdraw_confirm') . '</div>';
echo '<div class="stdEqualCols">';
echo '<div class="cancel"><button class="btn" name="cancel">' .
    Yii::t('motion', 'withdraw_no') . '</button></div>';
echo '<div class="withdraw"><button class="btn btn-danger" name="withdraw">' .
    Yii::t('motion', 'withdraw_yes') . '</button></div>';
echo '</div></div>';

echo Html::endForm();
