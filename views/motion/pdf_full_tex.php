<?php

use app\components\latex\{Exporter, Layout};
use app\models\db\{Motion, TexTemplate};
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

/**
 * @var TexTemplate $texTemplate
 * @var Motion[] $motions
 */

$layout             = new Layout();
$layout->assetRoot  = yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
$layout->pluginRoot = yii::$app->basePath . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
$layout->template   = $texTemplate->texLayout;
$layout->author     = Yii::t('export', 'default_creator');
$layout->title      = $motions[0]->motionType->titlePlural;

/** @var AntragsgruenApp $params */
$params = yii::$app->params;
try {
    $exporter = new Exporter($layout, $params);
    $contents = [];
    foreach ($motions as $motion) {
        $contents[] = \app\views\motion\LayoutHelper::renderTeX($motion);

        $amendments = $motion->getVisibleAmendmentsSorted();
        foreach ($amendments as $amendment) {
            $contents[] = \app\views\amendment\LayoutHelper::renderTeX($amendment, $texTemplate);
        }
    }
    echo $exporter->createPDF($contents);
} catch (Exception $e) {
    echo 'Error: ' . Html::encode($e);
    die();
}
