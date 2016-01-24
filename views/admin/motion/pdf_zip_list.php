<?php

use app\components\latex\Exporter;
use app\components\UrlHelper;

/**
 * @var $this yii\web\View
 * @var array $items
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$consultation = $controller->consultation;

/** @var \app\models\settings\AntragsgruenApp $params */
$params = \yii::$app->params;

$tmpZipFile = $params->tmpDir . uniqid('zip-');
$zip        = new ZipArchive();
if ($zip->open($tmpZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("cannot open <$tmpZipFile>\n");
}

$motions = $consultation->getVisibleMotionsSorted();
foreach ($motions as $motion) {
    \Yii::info('memo: ' . memory_get_usage(false) . " / " . memory_get_usage(false));
    $filename = $motion->getFilenameBase(false) . '.pdf';
    $zip->addFromString($filename, Exporter::createMotionPdf($motion));
}
$zip->close();

readfile($tmpZipFile);
unlink($tmpZipFile);
