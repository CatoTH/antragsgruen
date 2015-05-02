<?php

use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var Consultation $consultation
 * @var Motion $motion
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;

$this->title = 'Antrag bearbeiten: ' . $motion->getTitleWithPrefix();
$params->addBreadcrumb('Administration', UrlHelper::createUrl('admin/index'));
$params->addBreadcrumb('Anträge', UrlHelper::createUrl('admin/motion/index'));
$params->addBreadcrumb('Antrag');
$params->addCSS('/css/backend.css');

echo '<h1>' . Html::encode($motion->getTitleWithPrefix()) . '</h1>';

echo $controller->showErrors();



if ($motion->status == Motion::STATUS_SUBMITTED_UNSCREENED) {
    echo Html::beginForm('', 'post', ['class' => 'content', 'id' => 'motionScreenForm']);
    $newRev = $motion->titlePrefix;
    if ($newRev== '') {
        $newRev = $motion->consultation->getNextAvailableStatusString($motion->motionTypeId);
    }

    echo '<input type="hidden" name="titlePrefix" value="' . Html::encode($newRev) . '">';

    echo '<div style="text-align: center;"><button type="submit" class="btn btn-primary" name="screen">';
    echo Html::encode('Freischalten als ' . $newRev);
    echo '</button></div>';

    echo Html::endForm();

    echo "<br>";
}


echo Html::beginForm('', 'post', ['class' => 'content']);







echo Html::endForm();
