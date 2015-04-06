<?php

use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var string $mode
 */

$wording = $amendment->motion->consultation->getWording();

$this->title = $wording->get($mode == 'create' ? 'Änderungsantrag stellen' : 'Änderungsantrag bearbeiten');

$params->breadcrumbs[] = $this->title;
$params->breadcrumbs[] = 'Bestätigen';

echo '<h1>' . $wording->get('Änderungsantrag bestätigen') . '</h1>';

foreach ($amendment->getSortedSections(true) as $section) {
    if ($section->getSectionType()->isEmpty()) {
        continue;
    }
    echo '<section class="motion_text_holder">';
    echo '<h2>' . Html::encode($section->consultationSetting->title) . '</h3>';
    echo '<div class="textholder consolidated">';

    echo $section->getSectionType()->showSimple();

    echo '</div>';
    echo '</section>';
}


if ($amendment->changeExplanation != '') {
    echo '<div class="motion_text_holder">';
    echo '<h3>Begründung des Änderungsantrags</h3>';
    echo '<div class="content">';
    echo $amendment->changeExplanation;
    echo '</div>';
    echo '</div>';
}


echo '<div class="motion_text_holder">
        <h3>AntragstellerInnen</h3>

        <div class="content">
            <ul>';

foreach ($amendment->getInitiators() as $unt) {
    echo '<li style="font-weight: bold;">' . $unt->getNameWithResolutionDate(true) . '</li>';
}

foreach ($amendment->getSupporters() as $unt) {
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
