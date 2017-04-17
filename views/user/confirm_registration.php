<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var string $errors
 * @var string $backUrl
 * @var string $email
 */

$this->title = \Yii::t('user', 'confirm_title');

echo '<h1>' . \Yii::t('user', 'confirm_title') . '</h1>
<div class="content">';

if ($errors != '') {
    echo '<div class="alert alert-danger" role="alert">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <span class="sr-only">Error:</span>' . Html::encode($errors) . '</div>';
} else {
    echo '<div class="alert alert-info" role="alert">
        <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
        ' . \Yii::t('user', 'confirm_mail_sent') . '
    </div>';
}

$params = ['user/confirmregistration', 'backUrl' => $backUrl];
if ($email) {
    $params['email'] = $email;
}
echo Html::beginForm(UrlHelper::createUrl($params), 'post', ['id' => 'confirmAccountForm']);

echo '<div class="row"><div class="form-group col-md-6">
    <label for="username">' . \Yii::t('user', 'confirm_username') . ':</label>
    <input type="text" value="' . Html::encode($email) . '" id="username" name="email" class="form-control" ';
if ($email != '') {
    echo "disabled";
}
echo '>
        </div></div>

    <div class="row"><div class="form-group col-md-6">
        <label for="code">' . \Yii::t('user', 'confirm_code') . ':</label>
        <input type="text" name="code" value="" id="code" class="form-control">
    </div></div>

    <div class="row"><div class="col-md-6">
        <input type="submit" value="' . \Yii::t('user', 'confirm_btn_do') . '" class="btn btn-primary">
    </div></div>
    ';

echo Html::endForm();

echo '</div>';
