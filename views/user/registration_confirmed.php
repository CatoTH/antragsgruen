<?php

/**
 * @var yii\web\View $this
 * @var bool $needsAdminScreening
 */

$this->title = Yii::t('user', 'confirmed_title');
?>

<h1><?= Yii::t('user', 'confirmed_title') ?></h1>

<div class="content">
    <?php
    if ($needsAdminScreening) {
        ?>
        <div class="alert alert-info confirmedScreeningMsg" role="alert">
            <span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
            <?= Yii::t('user', 'confirmed_screening_msg') ?>
        </div>
        <?php
    } else {
        ?>
        <div class="alert alert-success" role="alert">
            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
            <?= Yii::t('user', 'confirmed_msg') ?>
        </div>
        <?php
    }
    ?>
</div>
