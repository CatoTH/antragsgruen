<?php

/**
 * @var yii\web\View $this
 * @var \app\models\db\Site $site
 * @var bool $policyWarning
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = 'Zugang gelöscht';

echo '<h1>' . 'Zugang gelöscht' . '</h1>
<div class="content">
    <div class="alert alert-success" role="alert">
        <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
        <span class="sr-only">Success:</span>
        Der Zugang wurde gelöscht.
    </div>
</div>';
