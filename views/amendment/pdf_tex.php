<?php

use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var Amendment $amendment
 */

try {
    echo \app\views\amendment\LayoutHelper::createPdf($amendment);
} catch (\Exception $e) {
    echo 'Ein Fehler trat auf: ' . Html::encode($e);
    die();
}
