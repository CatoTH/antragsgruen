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


echo '<h1>' . $wording->get("Antrag eingereicht") . '</h1>';

// @TODO
//echo $text = $antrag->veranstaltung->getStandardtext("antrag_eingereicht")->getHTMLText();

echo Html::beginForm(\app\components\UrlHelper::createUrl('consultation/index'));
echo '<p><button type="submit" class="btn btn-success">Zurück zur Startseite</button></p>';
echo Html::endForm();
