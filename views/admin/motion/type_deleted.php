<?php

use app\components\UrlHelper;

/**
 * @var $this yii\web\View
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('admin', 'motion_type_deleted_head');
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_types'));

echo '<h1>' . $this->title . '</h1>';


echo '<div class="content">';
echo '<div class="alert alert-success" role="alert">';
echo Yii::t('admin', 'motion_type_deleted_msg');
echo '</div></div>';
