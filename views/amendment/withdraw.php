<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$motion = $amendment->motion;

$layout->addBreadcrumb($motion->motionType->titleSingular, UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb('Änderungsantrag', UrlHelper::createAmendmentUrl($amendment));
$layout->addBreadcrumb('Zurückziehen');

$this->title = 'Zurückziehen' . ': ' . $amendment->getTitle();

echo '<h1>' . 'Zurückziehen' . ': ' . Html::encode($amendment->getTitle()) . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'content withdrawForm']);

echo '<div class="ask">Willst du diesen Änderungsantrag wirklich zurückziehen?</div>';
echo '<div class="row">';
echo '<div class="cancel col-md-6"><button class="btn" name="cancel">Doch nicht</button></div>';
echo '<div class="withdraw col-md-6"><button class="btn btn-danger" name="withdraw">Zurückziehen</button></div>';
echo '</div></div>';

echo Html::endForm();
