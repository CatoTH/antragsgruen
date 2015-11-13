<?php

use app\components\latex\Exporter;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

try {
    echo Exporter::createMotionPdf($motion);
} catch (\Exception $e) {
    echo 'Ein Fehler trat auf: ' . Html::encode($e);
}
