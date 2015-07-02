<?php

/**
 * @var Motion $motion
 */

use app\components\LaTeXExporter;
use app\models\db\Motion;
use app\models\settings\AntragsgruenApp;
use app\models\settings\LaTeX;
use yii\helpers\Html;

$initiators = [];
foreach ($motion->getInitiators() as $init) {
    $initiators[] = $init->getNameWithResolutionDate(false);
}
$initiatorsStr = implode(', ', $initiators);

$latex               = new LaTeX();
$latex->assetRoot    = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
$latex->templateFile = \yii::$app->basePath . DIRECTORY_SEPARATOR .
    'assets' . DIRECTORY_SEPARATOR . 'motion_std.tex';
$latex->author       = implode(', ', $initiators);
$latex->title        = $motion->title;
$latex->titlePrefix  = $motion->titlePrefix;
$latex->titleLong    = $motion->title;

$intro                  = explode("\n", $motion->consultation->getSettings()->pdfIntroduction);
$latex->introductionBig = $intro[0];
if (count($intro) > 1) {
    array_shift($intro);
    $latex->introductionSmall = implode("\n", $intro);
} else {
    $latex->introductionSmall = '';
}

$latex->motionDataTable = 'Antragsteller/innen:   &   ';
$latex->motionDataTable .= LaTeXExporter::encodePlainString(implode(', ', $initiators)) . '   \\\\';


$latex->text = '';
foreach ($motion->getSortedSections(true) as $section) {
    $latex->text .= $section->getSectionType()->getMotionTeX();
}

/** @var AntragsgruenApp $params */
$params = \yii::$app->params;
try {
    echo LaTeXExporter::createPDF($latex, $params);
} catch (\Exception $e) {
    echo 'Ein Fehler trat auf: ' . Html::encode($e);
}
