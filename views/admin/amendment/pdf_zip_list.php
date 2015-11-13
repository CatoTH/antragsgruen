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

$tmpZipFile   = $params->tmpDir . uniqid('zip-');
$zip = new ZipArchive();
if ($zip->open($tmpZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("cannot open <$tmpZipFile>\n");
}

$motions = $consultation->getVisibleMotionsSorted();
foreach ($motions as $motion) {
    $amendments = $motion->getVisibleAmendmentsSorted();
    foreach ($amendments as $amendment) {
        $zip->addFromString($amendment->titlePrefix . '.pdf',Exporter::createAmendmentPdf($amendment));
    }
}
$zip->close();

readfile($tmpZipFile);
unlink($tmpZipFile);
