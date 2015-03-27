<?php

use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\ConsultationSettingsMotionSection;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;

$this->title                                              = 'Antrags-Abschnitte';
$params->breadcrumbs[UrlHelper::createUrl('admin/index')] = 'Administration';
$params->breadcrumbs[]                                    = 'Abschnitte';
$params->addCSS('/css/admin.css');
$params->addJS('/js/Sortable/Sortable.min.js');
$params->addJS('/js/admin.js');

echo '<h1>Antrags-Abschnitte</h1>';

echo $controller->showErrors();


echo '<div class="content">';

echo Html::beginForm();

$renderSection = function (ConsultationSettingsMotionSection $section, Consultation $consultion) {
    $sectionId = IntVal($section->id);
    $sectionName = 'sections[' . $sectionId . ']';

    echo '<li><span class="drag-handle">&#9776;</span>';
    echo '<div class="toprow">';

    echo Html::dropDownList(
        $sectionName . '[type]',
        $section->type,
        ConsultationSettingsMotionSection::getTypes(),
        ['required' => 'required', 'class' => 'form-control sectionType']
    );

    echo '<label class="sectionTitle"><span class="sr-only">Name des Abschnitts</span>';
    echo '<input type="text" name="' . $sectionName . '[title]" ';
    echo 'value="' . Html::encode($section->title) . '" required placeholder="Titel" class="form-control">';
    echo '</label>';

    echo '</div><div class="bottomrow">';

    echo '<label>Nur anzeigen für Typ: ';
    echo '<select name="' . $sectionName . '[motionType]" size="1"><option value="">- alle -</option>';
    foreach ($consultion->motionTypes as $type) {
        echo '<option value="' . $type->id . '"';
        if ($type->id == $section->motionTypeId) {
            echo ' selected';
        }
        echo '>' . Html::encode($type->title) . '</option>';
    }
    echo '</select></label><br>';

    // @TODO Tags

    echo '<label class="fixedWidthLabel">';
    echo Html::checkbox($sectionName . '[fixedWidth]', $section->fixedWidth, ['class' => 'fixedWidth']);
    echo 'Feste Zeichenbreite</label>';

    echo '<label class="lineNumbersLabel">';
    echo Html::checkbox($sectionName . '[lineNumbers]', $section->lineNumbers, ['class' => 'lineNumbers']);
    echo 'Zeilennummern</label>';

    echo '</div><div class="commentrow">';

    echo '<label class="commentNone">';
    $val = ConsultationSettingsMotionSection::COMMENTS_NONE;
    echo Html::radio($sectionName . '[hasComments]', ($section->hasComments == $val), ['value' => $val]);
    echo ' Keine</label> ';

    echo '<label class="commentSection">';
    $val = ConsultationSettingsMotionSection::COMMENTS_SECTION;
    echo Html::radio($sectionName . '[hasComments]', ($section->hasComments == $val), ['value' => $val]);
    echo ' Für den gesamten Abschnitt</label> ';

    echo '<label class="commentParagraph">';
    $val = ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS;
    echo Html::radio($sectionName . '[hasComments]', ($section->hasComments == $val), ['value' => $val]);
    echo ' Pro Absatz</label> ';

    echo '</div></li>';
};


echo '<ul id="sectionsList">';
foreach ($consultation->motionSections as $section) {
    $renderSection($section, $consultation);
}
echo '</ul>';

echo '<button type="submit" name="save" class="btn btn-primary">Speichern</button>';

$params->addOnLoadJS('$.AntragsgruenAdmin.sectionsEdit();');

echo Html::endForm();

echo '<ul style="display: none;" id="sectionTemplate">';
$renderSection(new ConsultationSettingsMotionSection(), $consultation);
echo '</ul>';


echo '</div>';
