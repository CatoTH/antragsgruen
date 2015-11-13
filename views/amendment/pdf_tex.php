<?php

use app\components\latex\Exporter;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var Amendment $amendment
 */

try {
    echo Exporter::createAmendmentPdf($amendment);
} catch (\Exception $e) {
    echo 'Ein Fehler trat auf: ' . Html::encode($e);
    die();
}
