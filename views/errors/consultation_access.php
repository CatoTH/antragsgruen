<?php

/**
 * @var yii\web\View $this
 */

/** @var \app\controllers\admin\IndexController $controller */
$controller            = $this->context;
$layout                = $controller->layoutParams;
$layout->robotsNoindex = true;
$this->title           = \Yii::t('user', 'access_denied_title');

echo '<h1>' . \Yii::t('user', 'access_denied_title') . '</h1>

<div class="content">' . \Yii::t('user', 'access_denied_body') . '</div>';
