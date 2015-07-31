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

$this->title = 'Meine Einstellungen';
$layout->addBreadcrumb('Einstellungen');
$layout->robotsNoindex = true;


echo '<h1>Mein Zugang</h1>' .
    Html::beginForm('', 'post', ['class' => 'userAccountForm content form-horizontal']);

echo $controller->showErrors();

echo '
<div class="form-group">
   <label class="col-md-3 control-label" for="userName">' . 'Name' . ':</label>
   <div class="col-md-4">
        <input type="text" name="name" value="' . Html::encode($user->name) . '" class="form-control"
        id="userName" required>
   </div>
</div>
<div class="form-group">
   <label class="col-md-3 control-label" for="userPwd">' . 'Passwort ändern' . ':</label>
   <div class="col-md-4">
        <input type="password" name="pwd" value="" class="form-control" id="userPwd"
        placeholder="Leer lassen, falls unverändert" data-min-len="' . $pwMinLen . '">
   </div>
</div>
<div class="form-group">
   <label class="col-md-3 control-label" for="userPwd2">' . 'Passwort bestätigen' . ':</label>
   <div class="col-md-4">
        <input type="password" name="pwd2" value="" class="form-control" id="userPwd2">
   </div>
</div>';
if ($user->email) {
    echo '<div class="form-group">
    <div class="col-md-3 control-label label">E-Mail-Adresse:</div>
    <div class="col-md-9">';
    if ($user->emailConfirmed) {
        echo Html::encode($user->email);
    } else {
        echo '<span style="color: gray;">' . Html::encode($user->email) . '</span> (unbestätigt)';
    }

    echo '<div class="checkbox">
        <label>' .
        Html::checkbox('emailBlcklist', $emailBlacklisted) .
        'Jeglichen Mail-Versand an diese Adresse unterbinden
        </label>
      </div>';

    echo '</div>
</div>';
} else {
    echo '<div class="form-group">
   <label class="col-md-3 control-label" for="userEmail">' . 'E-Mail-Adresse' . ':</label>
   <div class="col-md-4">
        <input type="email" name="email" value="" class="form-control" id="userEmail">
   </div>
</div>';
}
echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">Speichern</button>
</div><br><br>
' . Html::endForm();


echo '<h2 class="green">' . 'Zugang löschen' . '</h2>' .
    Html::beginForm('', 'post', ['class' => 'accountDeleteForm content']) .
    '
    <div class="accountEditExplanation alert alert-info" role="alert">
    Hier kannst du diesen Zugang von Antragsgrün löschen. Du erhältst keine E-Mail-Benachrichtigungen mehr,
    ein Login ist auch nicht mehr möglich. Deine E-Mail-Adresse, Name, Passwort usw. werden damit aus unserem
    System gelöscht.<br>
    Eingebrachte (Änderungs-)Anträge bleiben aber erhalten. Um eingebrachte Anträge zu entfernen,
    wende dich bitte an die AdministratorInnen der jeweiligen Unterseite.
    </div>
    <div class="row">
    <div class="col-md-6">
    <div class="checkbox">
        <label>' .
    Html::checkbox('accountDeleteConfirm') .
    'Löschen bestätigen
        </label>
      </div>
      </div>
      <div class="col-md-6" style="text-align: right;">
        <button type="submit" name="accountDelete" class="btn btn-danger">Löschen</button>
      </div>
     </div>
    ' . Html::endForm();


$layout->addOnLoadJS('$.Antragsgruen.accountEdit();');
