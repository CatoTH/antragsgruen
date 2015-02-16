<?php

use app\models\db\Consultation;
use app\models\db\ISupporter;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Consultation $consultation
 * @var \app\models\db\ISupporter $initiator
 * @var string $labelName
 * @var string $labelOrganization
 * @var bool $allowOther
 */

$settings = $consultation->getSettings();

echo '<fieldset class="supporterForm supporterFormStd">';

echo '<h4>AntragstellerIn</h4>';

$preOrga       = Html::encode($initiator->organization);
$preName       = Html::encode($initiator->name);
$preEmail      = Html::encode($initiator->contactEmail);
$prePhone      = Html::encode($initiator->contactPhone);
$preResolution = Html::encode($initiator->resolutionDate);
echo '<div class="initiatorData form-horizontal">';

if ($allowOther) {
    echo '<div class="checkbox"><label><input type="checkbox" name="andere_antragstellerIn"> ' .
        'Ich lege diesen Antrag für eine andere AntragstellerIn an <small>(Admin-Funktion)</small>
    </label></div>';
}

echo '<div class="form-group">
<label class="col-sm-3 control-label">Ich bin eine...</label>
<div class="col-sm-9">
<label class="radio-inline">
  <input type="radio" required name="Initiator[personType]" id="personTypeNatural"
    value="' . ISupporter::PERSON_NATURAL . '">
  Natürliche Person
</label>
<label class="radio-inline">
  <input type="radio" required name="Initiator[personType]" id="personTypeOrga"
    value="' . ISupporter::PERSON_ORGANIZATION . '">
  Organisation / Gremium
</label>
</div>
</div>

<div class="form-group">
  <label class="col-sm-3 control-label" for="initiatorName">' . $labelName . '</label>
  <div class="col-sm-4">
    <input type="text" class="form-control" id="initiatorName" name="Initiator[name]" value="' . $preName . '" required>
  </div>
</div>

<div class="form-group organizationRow">
  <label class="col-sm-3 control-label" for="initiatorOrga">' . $labelOrganization . '</label>
  <div class="col-sm-4">
    <input type="text" class="form-control" id="initiatorOrga" name="Initiator[organization]" value="' . $preOrga . '">
  </div>
</div>

<div class="form-group organizationRow">
  <label class="col-sm-3 control-label" for="ResolutionDate">Beschlussdatum</label>
  <div class="col-sm-4">
    <input type="text" class="form-control" id="ResolutionDate" name="Initiator[resolutionDate]"
        value="' . $preResolution . '">
  </div>
</div>

<div class="form-group">
  <label class="col-sm-3 control-label" for="initiatorEmail">E-Mail</label>
  <div class="col-sm-4">
    <input type="text" class="form-control" id="initiatorEmail" name="Initiator[contactEmail]" ';
if ($settings->motionNeedsEmail) {
    echo 'required';
}
echo 'value="' . $preEmail . '">
  </div>
</div>';

if ($settings->motionHasPhone) {
    echo '<div class="control-group telefon_row">
                <label class="control-label" for="Person_telefon">Telefon</label>
				<div class="controls telefon_row"><input';
    if ($settings->motionNeedsPhone) {
        echo ' required';
    }
    echo ' name="Initiator[contactPhone]" id="Person_telefon" type="text" maxlength="100" value="' . $prePhone . '">
    </div>
			</div>';
}
echo '</div>';

echo '</fieldset>';
