<?php

use app\components\Html;

/**
 * @var \app\components\view\View $this
 * @var \app\models\settings\Layout $layout
 * @var \app\models\db\Motion $motion
 */

$layout->robotsNoindex = true;

if (\app\components\RequestContext::getRequestValue('backUrl') !== null && \app\components\RequestContext::getRequestValue('backTitle') !== null) {
    $layout->addBreadcrumb(\app\components\RequestContext::getRequestValue('backTitle'), \app\components\RequestContext::getRequestValue('backUrl'));
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
