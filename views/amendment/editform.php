<?php

use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var string $mode
 * @var \app\models\forms\AmendmentEditForm $form
 * @var \app\models\db\Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;
$wording    = $consultation->getWording();

if ($form->motion->titlePrefix != '') {
    $title = $wording->get(
        $mode == 'create' ? 'Änderungsantrag zu %prefix% stellen' : 'Änderungsantrag zu %prefix% bearbeiten'
    );
    $this->title = str_replace('%prefix%', $form->motion->titlePrefix, $title);
} else {
    $this->title = $wording->get($mode == 'create' ? 'Änderungsantrag stellen' : 'Änderungsantrag bearbeiten');
}


$params->addJS('/js/ckeditor/ckeditor.js');
$params->breadcrumbs[] = $wording->get($mode == 'create' ? 'Änderungsantrag stellen' : 'Änderungsantrag bearbeiten');

echo '<h1>' . Html::encode($this->title) . '</h1>';

echo '<br><div class="alert alert-info col-md-10 col-md-offset-1" role="alert">';
echo 'Ändere hier den Antrag so ab, wie du ihn gern sehen würdest.<br>';
echo 'Unter &quot;<strong>Begründung</strong>&quot; kannst du die Änderung begründen.<br>';
echo 'Falls dein Änderungsantrag Hinweise an die Pogrammkommission enthält, kannst du diese als ' .
    '&quot;<strong>Redaktionellen Antrag</strong>&quot; beifügen.';
echo '</div><br style="clear: both;">';


echo $controller->showErrors();

echo '<div class="form content">';



$motionPolicy = $consultation->getMotionPolicy();
if ($motionPolicy::getPolicyID() != \app\models\policies\All::getPolicyID()) {
    echo '<fieldset>
                <legend>' . $wording->get("Voraussetzungen für einen Antrag"), '</legend>
            </fieldset>';

    echo $motionPolicy->getOnCreateDescription();
}

echo Html::beginForm(
    '',
    'post',
    ['id' => 'motionEditForm', 'class' => 'motionEditForm', 'enctype' => 'multipart/form-data']
);


if (\Yii::$app->user->isGuest) {
    echo '<div class="alert alert-warning jsProtectionHint" role="alert">';
    echo 'Um diese Funktion zu nutzen, muss entweder JavaScript aktiviert sein, oder du musst eingeloggt sein.';
    echo '</div>';
}


foreach ($form->sections as $section) {
    echo $section->getSectionType()->getAmendmentFormField();
}

$initiatorClass = $consultation->getAmendmentInitiatorFormClass();
echo $initiatorClass->getAmendmentInitiatorForm($consultation, $form, $controller);

echo '<div class="submitHolder"><button type="submit" name="save" class="btn btn-primary">';
echo '<span class="glyphicon glyphicon-chevron-right"></span> Weiter';
echo '</button></div>';

$params->addOnLoadJS('$.Antragsgruen.motionEditForm();');

echo Html::endForm();

echo '</div>';
