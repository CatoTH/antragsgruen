<?php

use app\components\UrlHelper;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TabularDataType;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var ConsultationMotionType $motionType
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

$renderSection = function (ConsultationSettingsMotionSection $section) {
    $sectionId = IntVal($section->id);
    if ($sectionId == 0) {
        $sectionId = '#NEW#';
    }
    $sectionName = 'sections[' . $sectionId . ']';

    echo '<li data-id="' . $sectionId . '" class="section' . $sectionId . '">';
    echo '<span class="drag-handle">&#9776;</span>';
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
    echo '</div>'; // bottomRow

    /**
     * @param TabularDataType $row
     * @param int $i
     * @param string $sectionName
     * @return string
     */
    $dataRowFormatter = function (TabularDataType $row, $i, $sectionName) {
        $str = '<li class="no' . $i . '">';
        $str .= '<span class="drag-data-handle">&#9776;</span>';
        $str .= '<input type="text" name="' . $sectionName . '[tabular][' . $row->rowId . '][title]"';
        $str .= ' placeholder="Angabe" value="' . Html::encode($row->title) . '" class="form-control">';
        $str .= '<select name="' . $sectionName . '[tabular][' . $row->rowId . '][type]" class="form-control">';
        foreach (TabularDataType::getDataTypes() as $dataId => $dataName) {
            $str .= '<option value="' . $dataId . '"';
            if ($row->type == $dataId) {
                $str .= ' selected';
            }
            $str .= '>' . Html::encode($dataName) . '</option>';
        }
        $str .= '</select>';
        $str .= '<a href="#" class="delRow glyphicon glyphicon-remove-circle"></a>';
        $str .= '</li>';
        return $str;
    };


    echo '<div class="tabularDataRow">';
    echo '<legend>Angaben:</legend>';
    echo '<ul>';
    if ($section->type == ISectionType::TYPE_TABULAR) {
        $rows = \app\models\sectionTypes\TabularData::getTabularDataRowsFromData($section->data);
        $i    = 0;

        foreach ($rows as $rowId => $row) {
            echo $dataRowFormatter($row, $i++, $sectionName);
        }
    }
    echo '</ul>';

    $newRow = new TabularDataType(['rowId' => '#NEWDATA#', 'type' => TabularDataType::TYPE_STRING, 'title' => '']);
    $template = $dataRowFormatter($newRow, 0, $sectionName);
    echo '<a href="#" class="addRow" data-template="' . Html::encode($template) . '">';
    echo '<span class="glyphicon glyphicon-plus-sign"></span> Zeile hinzufügen</a>';
    echo '</div>'; // tabularDataRow

    echo '</div></li>';
};


echo '<ul id="sectionsList">';
foreach ($motionType->motionSections as $section) {
    $renderSection($section);
}
echo '</ul>';

echo '<a href="#" class="sectionAdder"><span class="glyphicon glyphicon-plus-sign"></span> Abschnitt hinzufügen</a>';

echo '<div class="submitRow"><button type="submit" name="save" class="btn btn-primary">Speichern</button></div>';

$params->addOnLoadJS('$.AntragsgruenAdmin.sectionsEdit();');

echo Html::endForm();

echo '<ul style="display: none;" id="sectionTemplate">';
$renderSection(new ConsultationSettingsMotionSection());
echo '</ul>';
