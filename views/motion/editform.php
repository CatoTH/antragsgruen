<?php

/**
 * @var \yii\web\View $this
 * @var string $mode
 * @var \app\models\forms\MotionEditForm $form
 * @var \app\models\db\Consultation $consultation
 * @var bool $forceTag
 */
use app\models\db\ConsultationSettingsTag;
use yii\helpers\Html;

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;

$this->title = Yii::t('motion', $mode == 'create' ? 'Start a Motion' : 'Edit Motion');

$params->addJS('/js/ckeditor/ckeditor.js');
$params->addJS('/js/bower/moment/min/moment-with-locales.min.js');
$params->addJS('/js/bower/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js');
$params->addCSS('/js/bower/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css');

$params->addBreadcrumb($this->title);

echo '<h1>' . Html::encode($this->title) . '</h1>';

echo '<div class="form content hideIfEmpty">';

echo $controller->showErrors();

$motionPolicy = $consultation->getMotionPolicy();
if ($motionPolicy::getPolicyID() != \app\models\policies\All::getPolicyID()) {
    echo '<fieldset>
                <legend>' . Yii::t('motion', 'Prerequisites for a motion'), '</legend>
            </fieldset>';

    echo $motionPolicy->getOnCreateDescription();
}

if (\Yii::$app->user->isGuest) {
    echo '<div class="alert alert-warning jsProtectionHint" role="alert">';
    echo 'Um diese Funktion zu nutzen, muss entweder JavaScript aktiviert sein, oder du musst eingeloggt sein.';
    echo '</div>';
}

echo '</div>';


echo Html::beginForm(
    '',
    'post',
    ['id' => 'motionEditForm', 'class' => 'motionEditForm', 'enctype' => 'multipart/form-data']
);

echo '<div class="content">';

/** @var ConsultationSettingsTag[] $tags */
$tags = array();
foreach ($consultation->tags as $tag) {
    $tags[$tag->id] = $tag;
}

if (count($tags) == 1) {
    $keys = array_keys($tags);
    echo '<input type="hidden" name="tags[]" value="' . $keys[0] . '">';
} elseif (count($tags) > 0) {
    echo '<fieldset class="form-group"><label class="legend">Thema</label>';
    foreach ($tags as $id => $tag) {
        echo '<label class="radio-inline"><input name="tags[]" value="' . $id . '" type="radio" ';
        if (in_array($id, $form->tags)) {
            echo ' checked';
        }
        echo ' required> ' . Html::encode($tag->title) . '</label>';
    }
    echo '</fieldset>';
}

echo '</div>';


echo '<h2>Text</h2>';
echo '<div class="content">';

foreach ($form->sections as $section) {
    echo $section->getSectionType()->getMotionFormField();
}

echo '</div>';




$initiatorClass = $consultation->getMotionInitiatorFormClass();
echo $initiatorClass->getMotionInitiatorForm($consultation, $form, $controller);

echo '<div class="submitHolder content"><button type="submit" name="save" class="btn btn-primary">';
echo '<span class="glyphicon glyphicon-chevron-right"></span> Weiter';
echo '</button></div>';

$params->addOnLoadJS('$.Antragsgruen.motionEditForm();');

echo Html::endForm();
