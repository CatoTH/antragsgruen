<?php

use app\components\latex\Exporter;
use app\components\latex\Layout;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\TexTemplate;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

/**
 * @var TexTemplate $texTemplate
 * @var Motion $motion
 * @var Amendment[] $amendments
 */

$layout            = new Layout();
$layout->assetRoot = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
$layout->template  = $texTemplate->texLayout;
$layout->author    = 'Antragsgrün';
$layout->title     = $motion->getTitleWithPrefix();

/** @var AntragsgruenApp $params */
$params = \yii::$app->params;
try {
    $exporter = new Exporter($layout, $params);
    $contents = [];
    $contents[] = \app\views\motion\LayoutHelper::renderTeX($motion);

    foreach ($amendments as $amendment) {
        $contents[] = \app\views\amendment\LayoutHelper::renderTeX($amendment, $texTemplate);
    }
    echo $exporter->createPDF($contents);
} catch (\Exception $e) {
    echo 'Error: ' . Html::encode($e);
    die();
}
