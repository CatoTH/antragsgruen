<?php

use app\models\db\User;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var User $user
 * @var bool $emailBlacklisted
 * @var int $pwMinLen
 */

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = \Yii::t('user', 'my_acc_title');
$layout->addBreadcrumb(\Yii::t('user', 'my_acc_bread'));
$layout->robotsNoindex = true;
$layout->addAMDModule('frontend/AccountEdit');


$formUrl = \app\components\UrlHelper::createUrl('user/myaccount');
echo '<h1>' . \Yii::t('user', 'my_acc_title') . '</h1>' .
    Html::beginForm($formUrl, 'post', ['class' => 'userAccountForm content form-horizontal']);

echo $controller->showErrors();

echo '
<div class="form-group">
   <label class="col-md-4 control-label" for="userName">' . \Yii::t('user', 'name') . ':</label>
   <div class="col-md-4">
        <input type="text" name="name" value="' . Html::encode($user->name) . '" class="form-control"
        id="userName" required>
   </div>
</div>
<div class="form-group">
   <label class="col-md-4 control-label" for="userPwd">' . \Yii::t('user', 'pwd_change') . ':</label>
   <div class="col-md-4">
        <input type="password" name="pwd" value="" class="form-control" id="userPwd"
        placeholder="' . \Yii::t('user', 'pwd_change_hint') . '" data-min-len="' . $pwMinLen . '">
   </div>
</div>
<div class="form-group">
   <label class="col-md-4 control-label" for="userPwd2">' . \Yii::t('user', 'pwd_confirm') . ':</label>
   <div class="col-md-4">
        <input type="password" name="pwd2" value="" class="form-control" id="userPwd2">
   </div>
</div>';
if ($user->email) {
    echo '<div class="form-group emailExistingRow">
    <label class="col-md-4 control-label">' . \Yii::t('user', 'email_address') . ':</label>
    <div class="col-md-8"><span class="currentEmail">';
    if ($user->emailConfirmed) {
        echo Html::encode($user->email);
    } else {
        echo '<span style="color: gray;">' . Html::encode($user->email) . '</span> ' .
            '(' . \Yii::t('user', 'email_unconfirmed') . ')';
    }
    echo '</span><a href="#" class="requestEmailChange">' . \Yii::t('user', 'emailchange_call') . '</a>';

    $changeRequested = $user->getChangeRequestedEmailAddress();
    if ($changeRequested) {
        echo '<div class="changeRequested">' . \Yii::t('user', 'emailchange_requested') . ': ';
        echo Html::encode($changeRequested);
        echo '<button type="submit" name="resendEmailChange" class="link resendButton">' .
            \Yii::t('user', 'emailchange_resend') . '</button>';
        echo '</div>';
    }

    echo '<div class="checkbox">
        <label>' . Html::checkbox('emailBlacklist', $emailBlacklisted) . \Yii::t('user', 'email_blacklist') . '</label>
      </div>';

    echo '</div>
</div>';
}

echo '<div class="form-group emailChangeRow">
   <label class="col-md-4 control-label" for="userEmail">' . \Yii::t('user', 'email_address_new') . ':</label>
   <div class="col-md-4">';
$changeRequested = $user->getChangeRequestedEmailAddress();
if ($changeRequested) {
    echo '<div class="changeRequested">' . \Yii::t('user', 'emailchange_requested') . ': ';
    echo Html::encode($changeRequested);
    echo '<button type="submit" name="resendEmailChange" class="link resendButton">' .
            \Yii::t('user', 'emailchange_resend') . '</button>';
    echo '</div>';
}
echo '<input type="email" name="email" value="" class="form-control" id="userEmail">';

echo '</div>
</div>';

echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">' . \Yii::t('base', 'save') . '</button>
</div><br><br>
' . Html::endForm();


echo '<h2 class="green">' . \Yii::t('user', 'del_title') . '</h2>' .
    Html::beginForm($formUrl, 'post', ['class' => 'accountDeleteForm content']) .
    '<div class="accountEditExplanation alert alert-info" role="alert">' .
    \Yii::t('user', 'del_explanation') .
    '</div>
    <div class="row">
    <div class="col-md-6">
    <div class="checkbox">
        <label>' . Html::checkbox('accountDeleteConfirm') . \Yii::t('user', 'del_confirm') . '</label>
      </div>
      </div>
      <div class="col-md-6" style="text-align: right;">
        <button type="submit" name="accountDelete" class="btn btn-danger">' . \Yii::t('user', 'del_do') . '</button>
      </div>
     </div>
    ' . Html::endForm();

