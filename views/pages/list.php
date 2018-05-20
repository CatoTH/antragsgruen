<?php

/**
 * @var $this yii\web\View
 * @var string $pageKey
 * @var string $saveUrl
 * @var bool $admin
 */

use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;

$this->title = \Yii::t('pages', 'list_title');

?>
<h1><?= $this->title = \Yii::t('pages', 'list_title'); ?></h1>

<div class="content">
    <strong><?= \Yii::t('pages', 'list_edit') ?></strong>

</div>
