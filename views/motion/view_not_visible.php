<?php

use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Motion $motion
 */

/** @var \app\controllers\Base $controller */
$controller            = $this->context;
$layout                = $controller->layoutParams;
$layout->robotsNoindex = true;

if ($controller->isRequestSet('backUrl') && $controller->isRequestSet('backTitle')) {
    $layout->addBreadcrumb($controller->getRequestValue('backTitle'), $controller->getRequestValue('backUrl'));
}
if (!$motion->getMyConsultation()->getForcedMotion()) {
    $layout->addBreadcrumb($motion->motionType->titleSingular);
}

$this->title = Yii::t('motion', 'err_not_visible_title') . ' (' . $motion->getMyConsultation()->title . ')';

?>
<h1><?= Html::encode(Yii::t('motion', 'err_not_visible_title')) ?></h1>
<div class="content">
    <div class="alert alert-danger"><?= Yii::t('motion', 'err_not_visible') ?></div>
</div>
