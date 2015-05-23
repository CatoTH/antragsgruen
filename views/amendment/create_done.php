<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var string $mode
 */

$this->title = Yii::t('amend', $mode == 'create' ? 'Änderungsantrag stellen' : 'Änderungsantrag bearbeiten');

$params->breadcrumbs[] = $this->title;
$params->breadcrumbs[] = 'Bestätigen';


echo '<h1>' . Yii::t('amend', 'Änderungsantrag eingereicht') . '</h1>';

// @TODO
//echo $text = $antrag->veranstaltung->getStandardtext("antrag_eingereicht")->getHTMLText();

echo Html::beginForm(UrlHelper::createMotionUrl($amendment->motion), 'post', ['id' => 'motionConfirmedForm']);
echo '<p class="btnRow"><button type="submit" class="btn btn-success">Zurück zum Antrag</button></p>';
echo Html::endForm();
