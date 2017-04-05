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

$this->title = \Yii::t('motion', 'err_not_visible_yet_title') .
    ' (' . $motion->getMyConsultation()->title . ', Antragsgrün)';

include(__DIR__ . DIRECTORY_SEPARATOR . '_view_sidebar.php');

echo '<h1>' . Html::encode(\Yii::t('motion', 'err_not_visible_yet_title')) . '</h1>
<br><br>
<div class="row">
    <div class="alert alert-danger col-md-10 col-md-offset-1">' . \Yii::t('motion', 'err_not_visible_yet') . '</div>
</div>
<br><br>';
