<?php
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var array $collissions
 * @var Amendment[] $collidingAmendments
 */


echo '<h1>' . Html::encode($amendment->getTitle()) . ': ' . 'Änderungen übernehmen' . '</h1>';

echo '<pre>';
var_dump($collissions);
echo '</pre>';
