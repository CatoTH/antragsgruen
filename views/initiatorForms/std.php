<?php

$str      = '';
$settings = $consultation->getSettings();

if ($consultation->isAdminCurUser()) {
    $str .= '<label><input type="checkbox" name="andere_antragstellerIn"> ' .
        'Ich lege diesen Antrag für eine andere AntragstellerIn an
                <small>(Admin-Funktion)</small>
            </label>';
}

$str .= '<div class="antragstellerIn_daten">
			<div class="control-group name_row"><label class="control-label" for="Person_name">' . $label_name . '</label>
				<div class="controls name_row"><input name="User[name]" id="Person_name" type="text" maxlength="100" value="';
if ($initiator) {
    $str .= Html::encode($initiator->name);
}
$str .= '"></div>
			</div>

			<div class="control-group organisation_row">
			<label class="control-label" for="Person_organisation">' . $label_organisation . '</label>
			<div class="controls organisation_row">
			<input name="User[organisation]" id="Person_organisation" type="text" maxlength="100" value="';
/*
if ($initiator) {
    $str .= Html::encode($initiator->organisation);
}
*/
$str .= '"></div>
			</div>

			<div class="control-group email_row"><label class="control-label" for="Person_email">E-Mail</label>
				<div class="controls email_row"><input';
if ($settings->motionNeedsEmail) {
    $str .= ' required';
}
$str .= ' name="User[email]" id="Person_email" type="text" maxlength="200" value="';
if ($initiator) {
    $str .= Html::encode($initiator->email);
}
$str .= '"></div>
			</div>';

if ($settings->motionHasPhone) {
    $str .= '<div class="control-group telefon_row">
                <label class="control-label" for="Person_telefon">Telefon</label>
				<div class="controls telefon_row"><input';
    if ($settings->motionNeedsPhone) {
        $str .= ' required';
    }
    $str .= ' name="User[telefon]" id="Person_telefon" type="text" maxlength="100" value="';
    /*
    if ($initiator) {
        $str .= Html::encode(Us->telefon);
    }
    */
    $str .= '"></div>
			</div>';
}
$str .= '</div>';

return $str;
