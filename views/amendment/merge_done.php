<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var Motion $newMotion
 */

$this->title = 'Antrag geändert';

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->addBreadcrumb($newMotion->motionType->titleSingular, UrlHelper::createMotionUrl($newMotion));
if (!$consultation->getSettings()->hideTitlePrefix && $amendment->titlePrefix != '') {
    $layout->addBreadcrumb($amendment->titlePrefix, UrlHelper::createAmendmentUrl($amendment));
} else {
    $layout->addBreadcrumb(\Yii::t('amend', 'amendment'), UrlHelper::createAmendmentUrl($amendment));
}
$layout->addBreadcrumb('Änderungen übernehmen');

?>

<h1><?= \Yii::t('amend', 'merge1_done_title') ?></h1>

<div class="content">
    <div class="alert alert-success" role="alert">
        <?= \Yii::t('amend', 'merge1_done_str') ?>

        <div style="text-align: center; margin-top: 20px;"><?php
            echo Html::a(
                \Yii::t('amend', 'merge1_done_goto'),
                UrlHelper::createMotionUrl($newMotion),
                ['class' => 'btn btn-primary']
            );
            ?>
        </div>
    </div>
</div>
