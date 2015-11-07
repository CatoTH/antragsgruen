<?php

use app\components\latex\Exporter;
use app\components\latex\Layout;
use app\models\db\Motion;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$texTemplate = $motion->motionType->texTemplate;

$layout            = new Layout();
$layout->assetRoot = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
//$layout->templateFile = \yii::$app->basePath . DIRECTORY_SEPARATOR .
//    'assets' . DIRECTORY_SEPARATOR . 'motion_std.tex';
$layout->template = $texTemplate->texLayout;
$layout->author   = $motion->getInitiatorsStr();
$layout->title    = $motion->getTitleWithPrefix();

try {
    /** @var AntragsgruenApp $params */
    $params   = \yii::$app->params;
    $exporter = new Exporter($layout, $params);
    $content  = \app\views\motion\LayoutHelper::renderTeX($motion, $exporter);
    echo $exporter->createPDF([$content]);
} catch (\Exception $e) {
    echo 'Ein Fehler trat auf: ' . Html::encode($e);
}
