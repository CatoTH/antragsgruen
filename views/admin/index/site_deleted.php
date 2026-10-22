<?php

/**
 * @var \app\components\view\View $this
 * @var \app\models\settings\Layout $layout
 */


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
