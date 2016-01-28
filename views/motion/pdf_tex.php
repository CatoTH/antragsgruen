<?php

use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

try {
    echo \app\views\motion\LayoutHelper::createPdf($motion);
} catch (\Exception $e) {
    echo 'Ein Fehler trat auf: ' . Html::encode($e);
}
