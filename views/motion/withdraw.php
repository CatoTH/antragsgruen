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

$layout->addBreadcrumb($motion->motionType->titleSingular, UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb('Zurückziehen');

$this->title = 'Zurückziehen' . ': ' . $motion->getTitleWithPrefix();

echo '<h1>' . 'Zurückziehen' . ': ' . Html::encode($motion->getTitleWithPrefix()) . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'content withdrawForm']);

echo '<div class="ask">Willst du diesen Antrag wirklich zurückziehen?</div>';
echo '<div class="row">';
echo '<div class="cancel col-md-6"><button class="btn" name="cancel">Doch nicht</button></div>';
echo '<div class="withdraw col-md-6"><button class="btn btn-danger" name="withdraw">Zurückziehen</button></div>';
echo '</div></div>';

echo Html::endForm();
