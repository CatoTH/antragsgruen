<?php

/**
 * @var yii\web\View $this
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('admin', 'cons_delete_done');
$layout->addCSS('css/backend.css');

?>
<h1><?= Yii::t('admin', 'cons_delete_done') ?></h1>

<div class="content">
    <div class="alert alert-success" role="alert">
        <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
        <span class="sr-only">Success:</span>
        <?= Yii::t('admin', 'cons_delete_done') ?>
    </div>
</div>
