<?php

use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var string $mode
 * @var \app\controllers\Base $controller
 */

$controller = $this->context;
$wording = $motion->consultation->getWording();

$this->title = $wording->get($mode == 'create' ? 'Antrag stellen' : 'Antrag bearbeiten');
$controller->layoutParams->addBreadcrumb($this->title);
$controller->layoutParams->addBreadcrumb('Bestätigen');

echo '<h1>' . $wording->get('Antrag bestätigen') . ': ' . Html::encode($motion->title) . '</h1>';

foreach ($motion->getSortedSections(true) as $section) {
    if ($section->getSectionType()->isEmpty()) {
        continue;
    }
    echo '<section class="motionTextHolder">';
    echo '<h2>' . Html::encode($section->consultationSetting->title) . '</h3>';
    echo '<div class="textholder consolidated">';

    echo $section->getSectionType()->showSimple();

    echo '</div>';
    echo '</section>';
}

echo '<div class="motionTextHolder">
        <h3>AntragstellerInnen</h3>

        <div class="content">
            <ul>';

foreach ($motion->getInitiators() as $unt) {
    echo '<li style="font-weight: bold;">' . $unt->getNameWithResolutionDate(true) . '</li>';
}

foreach ($motion->getSupporters() as $unt) {
    echo '<li>' . $unt->getNameWithResolutionDate(true) . '</li>';
}
echo '
            </ul>
        </div>
    </div>';

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
