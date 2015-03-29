<?php

/**
 * @var \yii\web\View $this
 * @var string $mode
 * @var \app\models\forms\MotionEditForm $form
 * @var \app\models\db\Consultation $consultation
 * @var \app\models\db\ConsultationSettingsMotionType[] $motionTypes
 * @var array $hiddens
 * @var bool $jsProtection
 * @var bool $forceTag
 */
use app\models\db\ConsultationSettingsTag;
use yii\helpers\Html;

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;
$wording    = $consultation->getWording();

$this->title = $wording->get($mode == 'create' ? 'Antrag stellen' : 'Antrag bearbeiten');

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

foreach ($hiddens as $name => $value) {
    echo '<input type="hidden" name="' . Html::encode($name) . '" value="' . Html::encode($value) . '">';
}

if ($jsProtection) {
    echo '<div class="alert alert-warning jsProtectionHint" role="alert">';
    echo 'Um diese Funktion zu nutzen, muss entweder JavaScript aktiviert sein, oder du musst eingeloggt sein.';
    echo '</div>';
}

if (count($motionTypes) == 1) {
    echo '<input type="hidden" name="type" value="' . $motionTypes[0]->id . '">';
} else {
    echo '<fieldset class="form-group motionType">
    <label for="motionTitle">' . $wording->get('Typ') . '</label>';
    foreach ($motionTypes as $type) {
        echo '<div class="radio"><label>';
        echo Html::radio('type', $form->type == $type->id, ['value' => $type->id, 'id' => 'motionType' . $type->id]);
        echo Html::encode($type->title);
        echo '</label></div>';
    }
    echo '</fieldset>';
}

/** @var ConsultationSettingsTag[] $tags */
$tags = array();
foreach ($consultation->tags as $tag) {
    $tags[$tag->id] = $tag;
}

if (count($tags) == 1) {
    $keys = array_keys($tags);
    echo '<input type="hidden" name="tags[]" value="' . $keys[0] . '">';
} elseif (count($tags) > 0) {
    echo '<fieldset class="form-group"><label class="legend">Antragstyp</label>';
    foreach ($tags as $id => $tag) {
        echo '<label class="radio-inline"><input name="tags[]" value="' . $id . '" type="radio" ';
        if (in_array($id, $form->tags)) {
            echo ' checked';
        }
        echo ' required> ' . Html::encode($tag->title) . '</label>';
    }
    echo '</fieldset>';
}

foreach ($form->sections as $section) {
    echo $section->getSectionType()->getFormField();
}

$initiatorClass = $consultation->getMotionInitiatorFormClass();
echo $initiatorClass->getMotionInitiatorForm($consultation, $form, $controller);

echo '<div class="submitHolder"><button type="submit" name="save" class="btn btn-primary">';
echo '<span class="glyphicon glyphicon-ok"></span> Weiter';
echo '</button></div>';

$params->addOnLoadJS('$.Antragsgruen.motionEditForm();');

echo Html::endForm();

echo '</div>';
