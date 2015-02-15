<?php

use app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Consultation $consultation
 * @var \app\models\db\ISupporter $initiator
 * @var string $labelName
 * @var string $labelOrganization
 */


$settings = $consultation->getSettings();

if ($consultation->isAdminCurUser()) {
    echo '<label><input type="checkbox" name="andere_antragstellerIn"> ' .
        'Ich lege diesen Antrag für eine andere AntragstellerIn an
                <small>(Admin-Funktion)</small>
            </label>';
}

echo '<div class="antragstellerIn_daten">
			<div class="control-group name_row"><label class="control-label" for="Person_name">' . $labelName . '</label>
				<div class="controls name_row"><input name="User[name]" id="Person_name" type="text" maxlength="100" value="';
if ($initiator) {
    echo Html::encode($initiator->name);
}
echo '"></div>
			</div>

			<div class="control-group organisation_row">
			<label class="control-label" for="Person_organisation">' . $labelOrganization . '</label>
			<div class="controls organisation_row">
			<input name="User[organisation]" id="Person_organisation" type="text" maxlength="100" value="';
/*
if ($initiator) {
    echo Html::encode($initiator->organisation);
}
*/
echo '"></div>
			</div>

			<div class="control-group email_row"><label class="control-label" for="Person_email">E-Mail</label>
				<div class="controls email_row"><input';
if ($settings->motionNeedsEmail) {
    echo ' required';
}
echo ' name="User[email]" id="Person_email" type="text" maxlength="200" value="';
if ($initiator) {
    echo Html::encode($initiator->cont);
}
echo '"></div>
			</div>';

if ($settings->motionHasPhone) {
    echo '<div class="control-group telefon_row">
                <label class="control-label" for="Person_telefon">Telefon</label>
				<div class="controls telefon_row"><input';
    if ($settings->motionNeedsPhone) {
        echo ' required';
    }
    echo ' name="User[telefon]" id="Person_telefon" type="text" maxlength="100" value="';
    /*
    if ($initiator) {
        echo Html::encode(Us->telefon);
    }
    */
    echo '"></div>
			</div>';
}
echo '</div>';
