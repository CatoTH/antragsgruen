<?php

use app\models\db\Consultation;
use app\models\db\ISupporter;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Consultation $consultation
 * @var \app\models\db\ISupporter $initiator
 * @var bool $allowOther
 * @var bool $hasSupporters
 * @var bool $minSupporters
 * @var bool $supporterFulltext
 * @var bool $supporterOrga
 */

/** @var app\controllers\Base $controller */
$controller = $this->context;

$settings = $consultation->getSettings();
$wording = $consultation->getWording();

echo '<fieldset class="supporterForm supporterFormStd">';

echo '<h2>AntragstellerIn</h2>';

$preOrga       = Html::encode($initiator->organization);
$preName       = Html::encode($initiator->name);
$preEmail      = Html::encode($initiator->contactEmail);
$prePhone      = Html::encode($initiator->contactPhone);
$preResolution = Html::encode($initiator->resolutionDate);
echo '<div class="initiatorData form-horizontal content">';

if ($allowOther) {
    echo '<div class="checkbox"><label><input type="checkbox" name="andere_antragstellerIn"> ' .
        'Ich lege diesen Antrag für eine andere AntragstellerIn an <small>(Admin-Funktion)</small>
    </label></div>';
}

echo '<div class="form-group">
<label class="col-sm-3 control-label">Ich bin eine...</label>
<div class="col-sm-9">
<label class="radio-inline">';
echo Html::radio(
    'Initiator[personType]',
    $initiator->personType == ISupporter::PERSON_NATURAL,
    [
        'value' => ISupporter::PERSON_NATURAL,
        'id' => 'personTypeNatural',
    ]
);
echo ' Natürliche Person
</label>
<label class="radio-inline">';
echo Html::radio(
    'Initiator[personType]',
    $initiator->personType == ISupporter::PERSON_ORGANIZATION,
    [
        'value' => ISupporter::PERSON_ORGANIZATION,
        'id' => 'personTypeOrga',
    ]
);

echo ' Organisation / Gremium
</label>
</div>
</div>

<div class="form-group">
  <label class="col-sm-3 control-label" for="initiatorName">' . $wording->get('Name') . '</label>
  <div class="col-sm-4">
    <input type="text" class="form-control" id="initiatorName" name="Initiator[name]" value="' . $preName . '" required>
  </div>
</div>

<div class="form-group organizationRow">
  <label class="col-sm-3 control-label" for="initiatorOrga">' . $wording->get('Gremium, LAG...') . '</label>
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
    echo 'required ';
}
echo 'value="' . $preEmail . '">
  </div>
</div>';

if ($settings->motionHasPhone) {
    echo '<div class="form-group phone_row">
        <label class="col-sm-3 control-label" for="initiatorPhone">Telefon</label>
  <div class="col-sm-4">
    <input type="text" class="form-control" id="initiatorPhone" name="Initiator[contactPhone]" ';
    if ($settings->motionNeedsPhone) {
        echo 'required ';
    }
    echo 'value="' . $prePhone . '">
  </div>
</div>';
}
echo '</div>';

echo '</fieldset>';

$controller->layoutParams->addOnLoadJS(
    '$("#personTypeNatural, #personTypeOrga").on("click change", function() {
        if ($("#personTypeOrga").prop("checked")) {
            $(".initiatorData .organizationRow").show();
        } else {
            $(".initiatorData .organizationRow").hide();
        }
    }).first().trigger("change");'
);
