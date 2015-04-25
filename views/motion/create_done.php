<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var string $mode
 * @var \app\controllers\Base $controller
 */

$this->title = Yii::t('motion', $mode == 'create' ? 'Start a Motion' : 'Edit Motion');

$controller = $this->context;
$controller->layoutParams->addBreadcrumb($this->title);
$controller->layoutParams->addBreadcrumb('Bestätigen');


echo '<h1>' . Yii::t('motion', 'Motion submitted') . '</h1>';

echo '<div class="content">';
echo '<div class="alert alert-success" role="alert">';
if ($motion->status == Motion::STATUS_SUBMITTED_SCREENED) {
    echo \Yii::t('motion', 'motion_confirmed_visible');
}
if ($motion->status == Motion::STATUS_SUBMITTED_UNSCREENED) {
    echo \Yii::t('motion', 'motion_confirmed_screening');
}
echo '</div>';


echo Html::beginForm(UrlHelper::createUrl('consultation/index'), 'post', ['id' => 'motionConfirmedForm']);
echo '<p class="btnRow"><button type="submit" class="btn btn-success">Zurück zur Startseite</button></p>';
echo Html::endForm();
echo '</div>';
