<?php

/**
 * @var \yii\web\View $this
 * @var string $mode
 * @var \app\models\forms\MotionEditForm $form
 * @var \app\models\db\Consultation $consultation
 * @var bool $forceTag
 */
use app\components\HTMLTools;
use app\models\db\ConsultationSettingsTag;
use yii\helpers\Html;

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = $form->motionType->createTitle;

$layout->loadCKEditor();
$layout->loadDatepicker();

$layout->addBreadcrumb($this->title);

if ($form->agendaItem) {
    echo '<h1>' . Html::encode($form->agendaItem->title . ': ' . $this->title) . '</h1>';
} else {
    echo '<h1>' . Html::encode($this->title) . '</h1>';
}

echo '<div class="form content hideIfEmpty">';

echo $controller->showErrors();

$motionPolicy = $form->motionType->getMotionPolicy();
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
    if ($consultation->getSettings()->allowMultipleTags) {
        echo '<fieldset class="form-group"><label class="legend">Thema</label>';
        foreach ($tags as $id => $tag) {
            echo '<label class="checkbox-inline"><input name="tags[]" value="' . $id . '" type="checkbox" ';
            if (in_array($id, $form->tags)) {
                echo ' checked';
            }
            echo ' required> ' . Html::encode($tag->title) . '</label>';
        }
        echo '</fieldset>';
    } else {
        $layout->loadFuelux();
        $selected   = (count($form->tags) > 0 ? $form->tags[0] : 0);
        $tagOptions = [];
        foreach ($tags as $tag) {
            $tagOptions[$tag->id] = $tag->title;
        }
        echo '<div class="label">Thema:</div><div style="position: relative;">';
        echo HTMLTools::fueluxSelectbox('tags[]', $tagOptions, $selected, ['id' => 'tagSelect']);
        echo '</div>';
    }

}

echo '</div>';


echo '<h2 class="green">Text</h2>';
echo '<div class="content">';

foreach ($form->sections as $section) {
    echo $section->getSectionType()->getMotionFormField();
}

echo '</div>';


$initiatorClass = $form->motionType->getMotionInitiatorFormClass();
echo $initiatorClass->getMotionForm($form->motionType, $form, $controller);

echo '<div class="submitHolder content"><button type="submit" name="save" class="btn btn-primary">';
echo '<span class="glyphicon glyphicon-chevron-right"></span> Weiter';
echo '</button></div>';

$layout->addOnLoadJS('$.Antragsgruen.motionEditForm();');

echo Html::endForm();
