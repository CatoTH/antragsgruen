<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var string $name
 * @var string $message
 * @var int $httpStatus
 */

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$layout->robotsNoindex = true;

if (!isset($httpStatus)) {
    $httpStatus = 500;
}
if (!isset($name)) {
    $name = 'Error';
}

switch ($httpStatus) {
    case 404:
        if ($message == '') {
            $message = Yii::t('base', 'err_site_404');
        }
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        break;
    case 403:
        if ($message == '') {
            $message = Yii::t('base', 'err_site_403');
        }
        header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
        break;
    case 410:
        if ($message == '') {
            $message = Yii::t('base', 'err_site_410');
        }
        header($_SERVER['SERVER_PROTOCOL'] . ' 410 Gone');
        break;
    case 500:
        if ($message == '') {
            $message = Yii::t('base', 'err_site_500');
        }
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
        break;
}

$this->title = $name;

?>
<h1><?= Html::encode($this->title) ?></h1>
<div class="content">
    <div class="alert alert-danger"><?= $message ?></div>
</div>
