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

$this->title = 'Benachrichtigungen deaktivieren';
$layout->addBreadcrumb('Benachrichtigungen');
$layout->robotsNoindex = true;


echo '<h1>Benachrichtigungen abbestellen</h1>' .
    Html::beginForm('', 'post', ['class' => 'emailBlacklistForm content']);

echo $controller->showErrors();

echo '<div class="radio">
    <label>' .
    Html::radio('unsubscribeOption', false, ['class' => 'unsubscribeNone', 'value' => 'nothing']) .
    'Benachrichtigungen unverändert lassen' .
    '</label>
  </div>';

if ($consultation) {
    echo '<div class="radio">
    <label>' .
        Html::radio('unsubscribeOption', true, ['class' => 'unsubscribeConsultation', 'value' => 'consultation']) .
        str_replace('%NAME%', $consultation->title, 'Benachrichtigngen dieser Veranstaltung (%NAME%) abbestellen') .
        '</label>
  </div>';
}

echo '<div class="radio">
    <label>' .
    Html::radio('unsubscribeOption', false, ['class' => 'unsubscribeAll', 'value' => 'all']) .
    'Alle Antragsgrün-Benachrichtigungen abbestellen' .
    '</label>
  </div>

  <br>

  <div class="checkbox">
    <label>' .
    Html::checkbox('emailBlacklist', $isBlacklisted, ['class' => 'emailBlacklist']) .
    'Grundsätzlich keine E-Mails mehr an meine E-Mail-Adresse
    <small>(auch keine Passwort-Wiederherstellungs-Mails etc.)</small>' .
    '</label>
  </div>

    <br>

<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">Speichern</button>
</div><br><br>
' . Html::endForm();
