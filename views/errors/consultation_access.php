<?php

/**
 * @var yii\web\View $this
 * @var bool $askForPermission
 * @var bool $askedForPermission
 */

/** @var \app\controllers\admin\IndexController $controller */
$controller            = $this->context;
$layout                = $controller->layoutParams;
$layout->robotsNoindex = true;
$this->title           = Yii::t('user', 'access_denied_title');


use yii\helpers\Html; ?>
<h1><?= Yii::t('user', 'access_denied_title') ?></h1>

<div class="content">
    <div class="alert alert-danger noAccessAlert">
        <p><?= Yii::t('user', 'access_denied_body') ?></p>
    </div>

    <?php
    if ($askForPermission) {
        echo Html::beginForm('', 'post', ['class' => 'askPermissionForm']);

        echo '<button type="submit" name="askPermission" class="btn btn-success">';
        echo Yii::t('user', 'managed_account_ask_btn');
        echo '</button>';

        echo Html::endForm();
    }
    if ($askedForPermission) {
        echo '<div class="alert alert-info askedForPermissionAlert"><p>';
        echo Yii::t('user', 'managed_account_asked');
        echo '</p></div>';
    }
    ?>
</div>
