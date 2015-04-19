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


echo '<form method="POST" action="' . CHtml::encode($suche->getCurrentUrl('/admin/index/antragsliste', $this)) . '" style="padding: 20px;">';
echo '<input type="hidden" name="' . AntiXSS::createToken('save') . '" value="1">';

echo '<table class="adminMotionTable">';
echo '<thead><tr>
    <th></th>
    <th>Typ</th>
    <th>Antragsnr.</th>
    <th>Betreff</th>
    <th>Status</th>
    <th>AntragstellerInnen</th>
    <th>Aktion</th>
</tr></thead>';

foreach ($antraege as $antrag) {
    $url = $this->createUrl('/admin/antraege/update', ['id' => $antrag->id]);
    echo '<tr>';
    echo '<td><input type="checkbox" name="motions[]" value="' . $antrag->id . '" class="selectbox"></td>';
    echo '<td>A</td>';
    echo '<td><a href="' . CHtml::encode($url) . '">' . CHtml::encode($antrag->revision_name) . '</a></td>';
    echo '<td><a href="' . CHtml::encode($url) . '">' . CHtml::encode(trim($antrag->name) != '' ? $antrag->name : '-') . '</a></td>';
    echo '<td>' . CHtml::encode(Antrag::$STATI[$antrag->status]) . '</td>';
    $antragstell = [];
    foreach ($antrag->getAntragstellerInnen() as $pers) {
        $antragstell[] = $pers->name;
    }
    echo '<td>' . CHtml::encode(implode(", ", $antragstell)) . '</td>';
    echo '</tr>';
}
unset($antrag);


foreach ($aenderungsantraege as $aend) {
    $url = $this->createUrl('/admin/aenderungsantraege/update', ['id' => $aend->id]);
    echo '<tr>';
    echo '<td><input type="checkbox" name="amendments[]" value="' . $aend->id . '" class="selectbox"></td>';
    echo '<td>ÄA</td>';
    echo '<td><a href="' . CHtml::encode($url) . '">' . CHtml::encode($aend->revision_name) . '</a></td>';
    echo '<td><a href="' . CHtml::encode($url) . '">' . CHtml::encode(trim($aend->antrag->name) != '' ? $aend->antrag->name : '-') . '</a></td>';
    echo '<td>' . CHtml::encode(Aenderungsantrag::$STATI[$aend->status]) . '</td>';
    $antragstell = [];
    foreach ($aend->getAntragstellerInnen() as $pers) {
        $antragstell[] = $pers->name;
    }
    echo '<td>' . CHtml::encode(implode(", ", $antragstell)) . '</td>';
    echo '</tr>';
}
unset($aend);

echo '</table>';


echo '<section style="overflow: auto;">';

echo '<div style="float: left; line-height: 40px; vertical-align: middle;">';
echo '<a href="#" class="markAll">Alle</a> &nbsp; ';
echo '<a href="#" class="markNone">Keines</a> &nbsp; ';
echo '</div>';

echo '<div style="float: right;">Markierte: &nbsp; ';
echo '<button type="submit" class="btn btn-danger" name="delete">Löschen</button> &nbsp; ';
echo '<button type="submit" class="btn btn-info" name="withdraw">Ent-Freischalten</button> &nbsp; ';
echo '<button type="submit" class="btn btn-success" name="screen">Freischalten</button>';
echo '</div>';
echo '</section>';


echo '<script>$(function() {
$(".markAll").click(function(ev) {
    $(".adminMotionTable").find("input.selectbox").prop("checked", true);
    ev.preventDefault();
});
$(".markNone").click(function(ev) {
    $(".adminMotionTable").find("input.selectbox").prop("checked", false);
    ev.preventDefault();
});
});</script>';

echo '</form>';
