<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $newMotion
 * @var string $deleteDraftId
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->addBreadcrumb($newMotion->motionType->titleSingular, UrlHelper::createMotionUrl($newMotion));
$layout->addBreadcrumb('Überarbeitung kontrollieren');

$title       = str_replace('%NAME%', $newMotion->motionType->titleSingular, '%NAME% überarbeitet');
$this->title = $title . ': ' . $newMotion->getTitleWithPrefix();

echo '<h1>' . 'Überarbeitung kontrollieren' . '</h1>';


foreach ($newMotion->getSortedSections(true) as $section) {
    if ($section->getSectionType()->isEmpty()) {
        continue;
    }
    echo '<section class="motionTextHolder">';
    echo '<h2 class="green">' . Html::encode($section->consultationSetting->title) . '</h2>';
    echo '<div class="consolidated">';

    echo $section->getSectionType()->getSimple();

    echo '</div>';
    echo '</section>';
}


echo Html::beginForm('', 'post', ['id' => 'motionConfirmForm']);

echo '<div class="content">
        <div style="float: right;">
            <button type="submit" name="confirm" class="btn btn-success">
                <span class="glyphicon glyphicon-ok-sign"></span> Einreichen
            </button>
        </div>
        <div style="float: left;">
            <button type="submit" name="modify" class="btn">
                <span class="glyphicon glyphicon-remove-sign"></span> Korrigieren
            </button>
        </div>
    </div>';

echo Html::endForm();

if ($deleteDraftId) {
    $controller->layoutParams->addOnLoadJS('localStorage.removeItem(' . json_encode($deleteDraftId) . ');');
}
