<?php

/**
 * @var \yii\web\View $this
 * @var string $mode
 * @var \app\models\forms\MotionEditForm $form
 * @var \app\models\db\Consultation $consultation
 * @var array $hiddens
 * @var bool $jsProtection
 * @var bool $forceTag
 */
use app\components\UrlHelper;
use app\models\db\ConsultationSettingsMotionSection;
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
                <legend>' . $wording->get("Voraussetzungen für einen Antrag") , '</legend>
            </fieldset>';

    echo $motionPolicy->getOnCreateDescription();
}

echo Html::beginForm('', '', ['id' => 'motionCreateForm', 'class' => 'motionEditForm']);

foreach ($hiddens as $name => $value) {
    echo '<input type="hidden" name="' . Html::encode($name) . '" value="' . Html::encode($value) . '">';
}

if ($jsProtection) {
    echo '<div class="alert alert-warning jsProtectionHint" role="alert">';
    echo 'Um diese Funktion zu nutzen, muss entweder JavaScript aktiviert sein, oder du musst eingeloggt sein.';
    echo '</div>';
}

echo '<fieldset class="form-group">
    <label for="motionTitle">Überschrift</label>
    <input type="text" class="form-control" id="motionTitle" name="title" value="' . Html::encode($form->title) . '">
  </fieldset>';

/** @var ConsultationSettingsTag][] $tags */
$tags = array();
if ($forceTag !== null) {
    $tags[$forceTag] = ConsultationSettingsTag::findOne($forceTag);
} else {
    foreach ($consultation->tags as $tag) {
        $tags[$tag->id] = $tag;
    }
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
        echo ' required> ' . Html::encode($tag->name) . '</label>';
    }
    echo '</fieldset>';
}


foreach ($consultation->motionSections as $section) {
    $sid = $section->id;

    echo '<fieldset class="form-group wysiwyg-textarea"';
    echo ' data-maxLen="' . $section->maxLen . '"';
    echo ' data-fullHtml="' . ($section->type == ConsultationSettingsMotionSection::TYPE_TEXT_HTML ? 1 : 0) . '"';
    echo '><label for="texts_' . $sid . '">' . Html::encode($section->title) . '</label>';

    if ($section->maxLen > 0) {
        echo '<div class="max_len_hint">';
        echo '<div class="calm">Maximale Länge: ' . $section->maxLen . ' Zeichen</div>';
        echo '<div class="alert">Text zu lang - maximale Länge: ' . $section->maxLen . ' Zeichen</div>';
        echo '</div>';
    }

    echo '<div class="textFullWidth">';
    echo '<div><textarea id="texts_' . $sid . '" name="texts[' . $sid . ']" rows="5" cols="80">';
    echo Html::encode(isset($form->texts[$sid]) ? $form->texts[$sid] : '');
    echo '</textarea></div></div>';
    echo '</fieldset>';
}

$initiatorClass = $consultation->getMotionInitiatorFormClass();
echo $initiatorClass->getMotionInitiatorForm($consultation, $form, $this->context);

echo '<div class="submitHolder"><button type="submit" name="create" class="btn btn-primary">';
echo '<span class="glyphicon glyphicon-ok"></span> Weiter';
echo '</button></div>';

$params->addOnLoadJS('$.Antragsgruen.motionEditForm();');

echo Html::endForm();

echo '</div>';
