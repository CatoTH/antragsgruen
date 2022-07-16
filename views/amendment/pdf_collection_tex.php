<?php

use app\components\latex\{Exporter, Layout};
use app\models\db\Amendment;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

/**
 * @var Amendment[] $amendments
 * @var \app\models\db\TexTemplate $texTemplate
 */

$layout             = new Layout();
$layout->assetRoot  = Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
$layout->pluginRoot = Yii::$app->basePath . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
$layout->template   = $texTemplate->texLayout;
$layout->author     = Yii::t('export', 'default_creator');
$layout->title      = Yii::t('export', 'all_amendments_title');

try {
    $exporter = new Exporter($layout, AntragsgruenApp::getInstance());
    $contents = [];
    foreach ($amendments as $amendment) {
        try {
            $contents[] = \app\views\amendment\LayoutHelper::renderTeX($amendment, $texTemplate);
        } catch (Exception $e) {
            // Skip this amendment
        }
    }
    echo $exporter->createPDF($contents);
} catch (Exception $e) {
    echo 'An error occurred: ' . Html::encode($e);
    die();
}
