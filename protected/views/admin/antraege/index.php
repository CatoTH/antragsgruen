<?php
/**
 * @var AntraegeController $this
 * @var Antrag[] $antraege
 * @var int|null $status_curr
 * @var $suche AdminAntragFilterForm $suche
 */

$this->breadcrumbs = array(
    Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
    Antrag::label(2),
);

/** @var CWebApplication $app */
$app = Yii::app();
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/typeahead/typeahead.bundle.js');


echo '<h1>' . GxHtml::encode(Antrag::label(2)) . '</h1>';

$action = $this->createUrl('/admin/antraege/index');
echo '<form method="GET" action="' . CHtml::encode($action) . '" style="padding: 20px;">';

echo $suche->getFilterFormFields();

echo '<div style="float: left;"><br><button type="submit" class="btn btn-success">Suchen</button></div>';

echo '</form><br style="clear: both;">';


$this->widget('zii.widgets.CListView', array(
    'dataProvider' => new CArrayDataProvider($antraege),
    'itemView'     => '_list',
));
