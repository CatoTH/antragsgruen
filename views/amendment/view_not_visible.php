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
    if (!$consultation->getSettings()->hideTitlePrefix && $amendment->titlePrefix != '') {
        $layout->addBreadcrumb($amendment->titlePrefix);
    } else {
        $layout->addBreadcrumb(\Yii::t('amend', 'amendment'));
    }
}

$this->title = \Yii::t('amend', 'err_not_visible_yet_title') . ' (' . $amendment->getMyConsultation()->title . ', Antragsgrün)';

include(__DIR__ . DIRECTORY_SEPARATOR . '_view_sidebar.php');

echo '<h1>' . Html::encode(\Yii::t('amend', 'err_not_visible_yet_title')) . '</h1>
<br><br>
<div class="row">
    <div class="alert alert-danger col-md-10 col-md-offset-1">' . \Yii::t('amend', 'err_not_visible_yet') . '</div>
</div>
<br><br>';
