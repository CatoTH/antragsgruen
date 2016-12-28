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
    $layout->addBreadcrumb(UrlHelper::createAmendmentUrl($amendment), $amendment->titlePrefix);
} else {
    $layout->addBreadcrumb(UrlHelper::createAmendmentUrl($amendment), \Yii::t('amend', 'amendment'));
}
$layout->addBreadcrumb('Änderungen übernehmen');

?>

<h1><?= /*\Yii::t('amend', 'amendment_submitted')*/
    'Antrag geändert' ?></h1>

<div class="content">
    <div class="alert alert-success" role="alert">
        Der Änderungsantrag wurde eingepflegt.

        <div style="text-align: center;"><?php
            echo Html::a(
                'Zur neuen Antragsversion',
                UrlHelper::createMotionUrl($newMotion),
                ['class' => 'btn btn-primary']
            );
            ?>
        </div>
    </div>
</div>
