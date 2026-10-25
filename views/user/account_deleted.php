<?php

/**
 * @var \app\components\view\View $this
 * @var \app\models\settings\Layout $layout
 * @var \app\models\db\Site $site
 * @var bool $policyWarning
 */


$this->title = Yii::t('user', 'deleted_title');
?>
<h1><?= Yii::t('user', 'deleted_title') ?></h1>
<div class="content">
    <div class="alert alert-success" role="alert">
        <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
        <span class="sr-only"><?= Yii::t('base', 'aria_success') ?>:</span>
        <?= Yii::t('user', 'deleted_msg') ?>
    </div>
</div>
