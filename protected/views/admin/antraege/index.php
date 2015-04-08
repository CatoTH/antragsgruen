<?php
/**
 * @var AntraegeController $this
 * @var Antrag[] $antraege
 * @var int[] $anzahl_stati
 * @var int $anzahl_gesamt
 * @var int|null $status_curr
 * @var array $tagsList
 * @var array $statusList
 * @var $suche AdminAntragFilterForm $suche
 */

$this->breadcrumbs = array(
    Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
    Antrag::label(2),
);

echo '<h1>' . GxHtml::encode(Antrag::label(2)) . '</h1>';


$action = $this->createUrl('/admin/antraege/index');
echo '<form method="GET" action="' . CHtml::encode($action) . '" style="padding: 20px;">';

echo '<label style="float: left; margin-right: 20px;">Status:<br>';
echo '<select name="Search[status]" size="1">';
echo '<option value="">- egal -</option>';
foreach ($statusList as $status_id => $status_name) {
    echo '<option value="' . $status_id . '" ';
    if ($suche->status !== null && $suche->status == $status_id) {
        echo ' selected';
    }
    echo '>' . CHtml::encode($status_name) . '</option>';
}
echo '</select></label>';

echo '<label style="float: left; margin-right: 20px;">Schlagwort:<br>';
echo '<select name="Search[tag]" size="1">';
echo '<option value="">- egal -</option>';
foreach ($tagsList as $tag_id => $tag_name) {
    echo '<option value="' . $tag_id . '" ';
    if ($suche->tag == $tag_id) {
        echo ' selected';
    }
    echo '>' . CHtml::encode($tag_name) . '</option>';
}
echo '</select></label>';


echo '<label style="float: left; margin-right: 20px;">Titel:<br>';
echo '<input type="text" name="Search[titel]" value="' . CHtml::encode($suche->titel) . '">';
echo '</label>';


echo '<div style="float: left;"><br><button type="submit" class="btn btn-success">Suchen</button></div>';

echo '</form><br style="clear: both;">';


$this->widget('zii.widgets.CListView', array(
    'dataProvider' => new CArrayDataProvider($antraege),
    'itemView'     => '_list',
));
