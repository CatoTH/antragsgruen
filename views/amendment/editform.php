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

$this->title = $wording->get($mode == 'create' ? 'Änderungsantrag stellen' : 'Änderungsantrag bearbeiten');

$params->addJS('/js/ckeditor/ckeditor.js');
$params->breadcrumbs[] = $this->title;

echo '<h1>' . Html::encode($this->title) . '</h1>';

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
echo '<span class="glyphicon glyphicon-ok"></span> Weiter';
echo '</button></div>';

$params->addOnLoadJS('$.Antragsgruen.motionEditForm();');

echo Html::endForm();

?>
<script>
/*
$("#amendmentForm").submit(function() {
    CKEDITOR.instances.ckeditor_toedit.plugins.lite.findPlugin(CKEDITOR.instances.ckeditor_toedit).acceptAll();
})
*/
</script>

<?


echo '</div>';
