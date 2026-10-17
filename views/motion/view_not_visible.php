<?php

use app\components\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Motion $motion
 */

/** @var \app\controllers\MotionController $controller */
$controller            = $this->context;
/** @var \app\models\settings\Layout $layout */
$layout                = $controller->layoutParams;
$layout->robotsNoindex = true;

if ($controller->isRequestSet('backUrl') && $controller->isRequestSet('backTitle')) {
    $layout->addBreadcrumb($controller->getRequestValue('backTitle'), $controller->getRequestValue('backUrl'));
}
if (!$motion->getMyConsultation()->getForcedMotion()) {
    $layout->addBreadcrumb($motion->motionType->getTitleSingularForDisplay());
}

$this->title = Yii::t('motion', 'err_not_visible_title') . ' (' . $motion->getMyConsultation()->title . ')';

?>
<h1><?= Html::encode(Yii::t('motion', 'err_not_visible_title')) ?></h1>
<div class="content">
    <div class="alert alert-danger"><?= Yii::t('motion', 'err_not_visible') ?></div>
</div>
