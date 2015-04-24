<?php

use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\sectionTypes\ISectionType;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;

$this->title = 'Antrags-Abschnitte';
$params->addBreadcrumb('Administration', UrlHelper::createUrl('admin/index'));
$params->addBreadcrumb('Abschnitte');
$params->addCSS('/css/backend.css');
$params->addJS('/js/bower/Sortable/Sortable.min.js');
$params->addJS('/js/backend.js');

echo '<h1>Antrags-Abschnitte</h1>';

echo Html::beginForm('', 'post', ['class' => 'content adminSectionsForm']);

echo $controller->showErrors();

$renderSection = function (ConsultationSettingsMotionSection $section, Consultation $consultion) {
    $sectionId = IntVal($section->id);
    if ($sectionId == 0) {
        $sectionId = '#NEW#';
    }
    $sectionName = 'sections[' . $sectionId . ']';

    echo '<li data-id="' . $sectionId . '"><span class="drag-handle">&#9776;</span>';
    echo '<div class="sectionContent">';
    echo '<div class="toprow">';

    echo '<a href="#" class="remover" title="Abschnitt löschen">';
    echo '<span class="glyphicon glyphicon-remove-circle"></span></a>';

    $attribs = ['class' => 'form-control sectionType'];
    if ($section->id > 0) {
        $attribs['disabled'] = 'disabled';
    }
    echo Html::dropDownList(
        $sectionName . '[type]',
        $section->type,
        ISectionType::getTypes(),
        $attribs
    );

    echo '<label class="sectionTitle"><span class="sr-only">Name des Abschnitts</span>';
    echo '<input type="text" name="' . $sectionName . '[title]" ';
    echo 'value="' . Html::encode($section->title) . '" required placeholder="Titel" class="form-control">';
    echo '</label>';

    echo '</div><div class="bottomrow"><div class="assignmentRow">';

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

    echo '</div><div class="optionsRow">';

    echo '<label class="fixedWidthLabel">';
    echo Html::checkbox($sectionName . '[fixedWidth]', $section->fixedWidth, ['class' => 'fixedWidth']);
    echo 'Feste Zeichenbreite</label>';

    echo '<label class="requiredLabel">';
    echo Html::checkbox($sectionName . '[required]', $section->required, ['class' => 'required']);
    echo 'Notwendig</label>';

    echo '<label class="lineNumbersLabel">';
    echo Html::checkbox($sectionName . '[lineNumbers]', $section->lineNumbers, ['class' => 'lineNumbers']);
    echo 'Zeilennummern</label>';

    echo '<label class="lineLength">';
    echo Html::checkbox($sectionName . '[maxLenSet]', ($section->maxLen != 0), ['class' => 'maxLenSet']);
    echo 'Längenbegrenzung</label>';
    echo '<label class="maxLenInput"><input type="number" min="1" name="' . $sectionName . '[maxLenVal]" value="';
    if ($section->maxLen > 0) {
        echo $section->maxLen;
    }
    if ($section->maxLen < 0) {
        echo -1 * $section->maxLen;
    }
    echo '"> Zeichen</label>';
    echo '<label class="lineLengthSoft">';
    echo Html::checkbox($sectionName . '[maxLenSoft]', ($section->maxLen < 0), ['class' => 'maxLenSoft']);
    echo 'Überschreitung erlauben';
    echo '</label>';

    echo '</div><div class="commAmendRow">';

    echo '<div class="commentRow">';
    echo '<div>Kommentare:</div>';

    echo '<label class="commentNone">';
    $val = ConsultationSettingsMotionSection::COMMENTS_NONE;
    echo Html::radio($sectionName . '[hasComments]', ($section->hasComments == $val), ['value' => $val]);
    echo ' Keine</label> ';

    echo '<label class="commentSection">';
    $val = ConsultationSettingsMotionSection::COMMENTS_SECTION;
    echo Html::radio($sectionName . '[hasComments]', ($section->hasComments == $val), ['value' => $val]);
    echo ' Gesamter Abschnitt</label> ';

    echo '<label class="commentParagraph">';
    $val = ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS;
    echo Html::radio($sectionName . '[hasComments]', ($section->hasComments == $val), ['value' => $val]);
    echo ' Pro Absatz</label> ';

    echo '</div>'; // commentRow

    echo '<label class="amendmentRow">';
    echo Html::checkbox($sectionName . '[hasAmendments]', ($section->hasAmendments == 1), ['class' => 'hasAmendments']);
    echo ' In Änderungsanträgen';
    echo '</label>';

    echo '</div>'; // commAmendRow
    echo '</div></div></li>';
};


echo '<ul id="sectionsList">';
foreach ($consultation->motionSections as $section) {
    $renderSection($section, $consultation);
}
echo '</ul>';

echo '<a href="#" class="sectionAdder"><span class="glyphicon glyphicon-plus-sign"></span> Abschnitt hinzufügen</a>';

echo '<div class="submitRow"><button type="submit" name="save" class="btn btn-primary">Speichern</button></div>';

$params->addOnLoadJS('$.AntragsgruenAdmin.sectionsEdit();');

echo Html::endForm();

echo '<ul style="display: none;" id="sectionTemplate">';
$renderSection(new ConsultationSettingsMotionSection(), $consultation);
echo '</ul>';
