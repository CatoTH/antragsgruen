<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $newMotion
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->addBreadcrumb($newMotion->motionType->titleSingular, UrlHelper::createMotionUrl($newMotion));
$layout->addBreadcrumb('Überarbeitung kontrollieren');

$title       = str_replace('%NAME%', $newMotion->motionType->titleSingular, '%NAME% überarbeitet');
$this->title = $title . ': ' . $newMotion->getTitleWithPrefix();

echo '<h1>' . Html::encode($this->title) . '</h1>
<div class="content">
<div class="alert alert-success" role="alert">';
echo 'Der Antrag wurde überarbeitet';
echo '</div>';


echo Html::beginForm(UrlHelper::createMotionUrl($newMotion), 'post', ['id' => 'motionConfirmedForm']);
echo '<p class="btnRow"><button type="submit" class="btn btn-success">Zum neuen Antrag</button></p>';
echo Html::endForm();

echo '</div>';
