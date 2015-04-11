<?php
/**
 * @var AntraegeController $this
 * @var Antrag[] $antraege
 * @var Aenderungsantrag[] $aenderungsantraege
 * @var int|null $status_curr
 * @var $suche AdminAntragFilterForm $suche
 */

$this->breadcrumbs = array(
    Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
    "Antragsliste",
);
$this->full_width = true;

$action = $this->createUrl('/admin/index/antragsliste');

/** @var CWebApplication $app */
$app = Yii::app();
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/typeahead/typeahead.bundle.js');

echo '<form method="GET" action="' . CHtml::encode($action) . '" style="padding: 20px;">';

echo $suche->getFilterFormFields();

echo '<div style="float: left;"><br><button type="submit" class="btn btn-success">Suchen</button></div>';

echo '</form><br style="clear: both;">';




echo '<table class="adminAntragsListe">';
echo '<thead><tr>
    <th>Antragsnr.</th>
    <th>Betreff</th>
    <th>Status</th>
    <th>AntragstellerInnen</th>
    <th>Aktion</th>
</tr></thead>';

foreach ($antraege as $antrag) {
    $url = $this->createUrl('/admin/antraege/update', ['id' => $antrag->id]);
    echo '<tr>';
    echo '<td><a href="' . CHtml::encode($url) . '">' . CHtml::encode($antrag->revision_name) . '</a></td>';
    echo '<td><a href="' . CHtml::encode($url) . '">' . CHtml::encode($antrag->name) . '</a></td>';
    echo '<td>' . CHtml::encode(Antrag::$STATI[$antrag->status]) . '</td>';
    $antragstell = [];
    foreach ($antrag->getAntragstellerInnen() as $pers) {
        $antragstell[] = $pers->name;
    }
    echo '<td>' . CHtml::encode(implode(", ", $antragstell)) . '</td>';
    echo '</tr>';
}


foreach ($aenderungsantraege as $aend) {
    $url = $this->createUrl('/admin/aenderungsantraege/update', ['id' => $aend->id]);
    echo '<tr>';
    echo '<td><a href="' . CHtml::encode($url) . '">' . CHtml::encode($aend->revision_name) . '</a></td>';
    echo '<td><a href="' . CHtml::encode($url) . '">' . CHtml::encode($aend->antrag->name) . '</a></td>';
    echo '<td>' . CHtml::encode(Aenderungsantrag::$STATI[$aend->status]) . '</td>';
    $antragstell = [];
    foreach ($aend->getAntragstellerInnen() as $pers) {
        $antragstell[] = $pers->name;
    }
    echo '<td>' . CHtml::encode(implode(", ", $antragstell)) . '</td>';
    echo '</tr>';
}


echo '</table>';