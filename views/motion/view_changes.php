<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $newMotion
 * @var Motion $oldMotion
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
if (!$newMotion->getMyConsultation()->getForcedMotion()) {
    $layout->addBreadcrumb($newMotion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($newMotion));
}
$layout->addBreadcrumb(\Yii::t('motion', 'diff_bc'));

$this->title = str_replace(
    ['%FROM%', '%TO%'],
    [$oldMotion->titlePrefix, $newMotion->titlePrefix],
    \Yii::t('motion', 'diff_title')
);
?>
<h1><?= Html::encode($this->title) ?></h1>
<div class="content">
    <?php
    echo $controller->showErrors();

    ?>
</div>