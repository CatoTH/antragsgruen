<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 */


$controller  = $this->context;
$this->title = 'Datenbankverbindung einrichten';

echo '<h1>' . 'Datenbankverbindung einrichten' . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'content dbinitForm']);

echo Html::endForm();
