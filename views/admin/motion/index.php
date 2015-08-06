<?php

use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var Consultation $consultation
 * @var Motion[] $motions
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = 'Anträge';
$layout->addBreadcrumb('Administration', UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb('Anträge');
$layout->addCSS('css/backend.css');

echo '<h1>Anträge</h1>';

echo $controller->showErrors();

echo '<div class="content">';
echo '<ul>';
foreach ($motions as $motion) {
    $url = UrlHelper::createUrl(['admin/motion/update', 'motionId' => $motion->id]);
    echo '<li><a href="' . Html::encode($url) . '" class="motionEditLink' . $motion->id . '">';
    echo Html::encode($motion->getTitleWithPrefix());
    echo '</a></li>';
}
echo '</ul>';
echo '</div>';
