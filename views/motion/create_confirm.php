<?php

use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var string $mode
 */

$wording = $motion->consultation->getWording();

$this->title = $wording->get($mode == 'create' ? 'Antrag stellen' : 'Antrag bearbeiten');

$params->breadcrumbs[] = $this->title;
$params->breadcrumbs[] = 'Bestätigen';

echo '<h1>' . $wording->get('Antrag bestätigen') . ': ' . Html::encode($motion->title) . '</h1>';

// Yii::app()->user->setFlash("info", $antrag->veranstaltung->getStandardtext("antrag_confirm")->getHTMLText());
// $this->widget('bootstrap.widgets.TbAlert');

foreach ($motion->getSortedSections() as $section) {
    echo '<section class="motion_text_holder">';
    echo '<h2>' . Html::encode($section->consultationSetting->title) . '</h3>';
    echo '<div class="textholder consolidated">';

    echo $section->data;
    /* // @TODO
    $absae = $antrag->getParagraphs();
    foreach ($absae as $i => $abs) {
        echo "<div class='absatz_text orig antragabsatz_holder antrags_text_holder_nummern'>";
        echo $abs->str_html;
        echo "</div>";
    }
    */
    echo '</div>';
    echo '</section>';
}

echo '<div class="motion_text_holder">
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
