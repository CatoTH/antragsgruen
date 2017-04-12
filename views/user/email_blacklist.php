<?php

use app\models\db\User;
use app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var User $user
 * @var Consultation $consultation
 * @var bool $isBlacklisted
 */

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = \Yii::t('user', 'no_noti_title');
$layout->addBreadcrumb(\Yii::t('user', 'no_noti_bc'));
$layout->robotsNoindex = true;


echo '<h1>' . \Yii::t('user', 'no_noti_title') . '</h1>' .
    Html::beginForm('', 'post', ['class' => 'emailBlacklistForm content']);

echo $controller->showErrors();

echo '<div class="radio">
    <label>' .
    Html::radio('unsubscribeOption', false, ['class' => 'unsubscribeNone', 'value' => 'nothing']) .
    \Yii::t('user', 'no_noti_unchanged') .
    '</label>
  </div>';

if ($consultation) {
    echo '<div class="radio">
    <label>' .
        Html::radio('unsubscribeOption', true, ['class' => 'unsubscribeConsultation', 'value' => 'consultation']) .
        str_replace('%NAME%', $consultation->title, \Yii::t('user', 'no_noti_consultation')) .
        '</label>
  </div>';
}

echo '<div class="radio">
    <label>' .
    Html::radio('unsubscribeOption', false, ['class' => 'unsubscribeAll', 'value' => 'all']) .
    \Yii::t('user', 'no_noti_all') .
    '</label>
  </div>

  <br>

  <div class="checkbox">
    <label>' .
    Html::checkbox('emailBlacklist', $isBlacklisted, ['class' => 'emailBlacklist']) .
    \Yii::t('user', 'no_noti_blacklist') .
    '</label>
  </div>

    <br>

<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">' . \Yii::t('user', 'no_noti_save') . '</button>
</div><br><br>
' . Html::endForm();
