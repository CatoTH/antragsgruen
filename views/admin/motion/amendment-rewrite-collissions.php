<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Amendment[] $amendments
 * @var array[] $collissions
 */

if (count($collissions) == 0) {
    echo '<div class="alert alert-success">' . 'Keine Konflikte zu bestehenden Änderungsanträgen' . '</div>';
    return;
}

echo '<div class="alert alert-danger">' . 'Es gibt Kollissionen:' . '</div>';
var_dump($collissions);
