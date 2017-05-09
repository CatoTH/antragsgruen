<?php

use app\components\latex\Exporter;
use app\components\latex\Layout;
use app\models\db\Amendment;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

/**
 * @var Amendment[] $amendments
 * @var \app\models\db\TexTemplate $texTemplate
 */

$layout            = new Layout();
$layout->assetRoot = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
$layout->template = $texTemplate->texLayout;
$layout->author   = 'Antragsgrün';
$layout->title    = \Yii::t('export', 'all_amendments_title');

/** @var AntragsgruenApp $params */
$params = \yii::$app->params;
try {
    $exporter = new Exporter($layout, $params);
    $contents = [];
    foreach ($amendments as $amendment) {
        $contents[] = \app\views\amendment\LayoutHelper::renderTeX($amendment, $texTemplate);
    }
    echo $exporter->createPDF($contents);
} catch (\Exception $e) {
    echo 'An error occurred: ' . Html::encode($e);
    die();
}
