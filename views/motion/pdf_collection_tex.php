<?php

use app\components\latex\{Exporter, Layout};
use app\models\db\{Amendment, IMotion, Motion, TexTemplate};
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

/**
 * @var TexTemplate $texTemplate
 * @var IMotion[] $imotions
 */

$motionType = $imotions[0]->getMyMotionType();
$layout             = new Layout();
$layout->assetRoot  = yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
$layout->pluginRoot = yii::$app->basePath . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
$layout->template   = $texTemplate->texLayout;
$layout->author     = Yii::t('export', 'default_creator');
$layout->title      = $motionType->titlePlural;

try {
    $exporter = new Exporter($layout, AntragsgruenApp::getInstance());
    $contents = [];
    foreach ($imotions as $imotion) {
        if (is_a($imotion, Motion::class)) {
          $contents[] = \app\views\motion\LayoutHelper::renderTeX($imotion);
        } elseif (is_a($imotion, Amendment::class)) {
            $contents[] = \app\views\amendment\LayoutHelper::renderTeX($imotion, $texTemplate);
        }
    }
    echo $exporter->createPDF($contents);
} catch (Exception $e) {
    echo 'Error: ' . Html::encode($e);
    die();
}
