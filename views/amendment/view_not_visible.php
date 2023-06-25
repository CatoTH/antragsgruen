<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Amendment $amendment
 */

/** @var \app\controllers\Base $controller */
$controller            = $this->context;
$layout                = $controller->layoutParams;
$consultation          = $amendment->getMyConsultation();
$layout->robotsNoindex = true;

if ($controller->isRequestSet('backUrl') && $controller->isRequestSet('backTitle')) {
    $layout->addBreadcrumb($controller->getRequestValue('backTitle'), $controller->getRequestValue('backUrl'));
    $layout->addBreadcrumb($amendment->getShortTitle());
} else {
    $motionUrl = UrlHelper::createMotionUrl($amendment->getMyMotion());
    $layout->addBreadcrumb($amendment->getMyMotion()->motionType->titleSingular, $motionUrl);
    if (!$consultation->getSettings()->hideTitlePrefix && $amendment->getFormattedTitlePrefix() !== '') {
        $layout->addBreadcrumb($amendment->getFormattedTitlePrefix());
    } else {
        $layout->addBreadcrumb(Yii::t('amend', 'amendment'));
    }
}

$this->title = Yii::t('amend', 'err_not_visible_title') . ' (' . $amendment->getMyConsultation()->title . ')';

?>
<h1><?= Html::encode(Yii::t('amend', 'err_not_visible_title')) ?></h1>
<div class="content">
    <div class="alert alert-danger"><?= Yii::t('amend', 'err_not_visible') ?></div>
</div>
