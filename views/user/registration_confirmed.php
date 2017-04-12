<?php

/**
 * @var yii\web\View $this
 */

$this->title = \Yii::t('user', 'confirmed_title');
?>

<h1><?= \Yii::t('user', 'confirmed_title') ?></h1>

<div class="content">
    <div class="alert alert-success" role="alert">
        <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
        <?=\Yii::t('user', 'confirmed_msg')?>
    </div>
</div>
