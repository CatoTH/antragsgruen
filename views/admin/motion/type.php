<?php

use app\components\HTMLTools;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TabularDataType;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var ConsultationMotionType $motionType
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = 'Antragstyp bearbeiten';
$layout->addBreadcrumb(\Yii::t('admin', 'bread_admin'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(\Yii::t('admin', 'bread_types'));

$layout->addCSS('css/backend.css');
$layout->addJS('js/backend.js');
$layout->addJS('js/bower/Sortable/Sortable.min.js');
$layout->loadDatepicker();

$myUrl = UrlHelper::createUrl(['admin/motion/type', 'motionTypeId' => $motionType->id]);

$policies = [];
foreach (IPolicy::getPolicies() as $policy) {
    $policies[$policy::getPolicyID()] = $policy::getPolicyName();
}
$locale = Tools::getCurrentDateLocale();


echo '<h1>Antragstyp bearbeiten</h1>';


echo Html::beginForm($myUrl, 'post', ['class' => 'adminTypeForm form-horizontal']);

echo '<div class="content">';

echo $controller->showErrors();


echo '<h3>' . \Yii::t('admin', 'motion_type_names') . '</h3>';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typeTitleSingular">';
echo \Yii::t('admin', 'motion_type_singular');
echo '</label><div class="col-md-9">';
$options = [
    'class'       => 'form-control',
    'id'          => 'typeTitleSingular',
    'placeholder' => \Yii::t('admin', 'motion_type_singular_pl'),
];
echo Html::textInput('type[titleSingular]', $motionType->titleSingular, $options);
echo '</div></div>';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typeTitlePlural">';
echo \Yii::t('admin', 'motion_type_plural');
echo '</label><div class="col-md-9">';
$options = [
    'class'       => 'form-control',
    'id'          => 'typeTitlePlural',
    'placeholder' => \Yii::t('admin', 'motion_type_plural_pl'),
];
echo Html::textInput('type[titlePlural]', $motionType->titlePlural, $options);
echo '</div></div>';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typeCreateTitle">';
echo \Yii::t('admin', 'motion_type_create_title');
echo '</label><div class="col-md-9">';

$options = [
    'class'       => 'form-control',
    'id'          => 'typeCreateTitle',
    'placeholder' => \Yii::t('admin', 'motion_type_create_placeh')
];
echo HTMLTools::smallTextarea('type[createTitle]', $options, $motionType->createTitle);
echo '</div></div>';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="pdfLayout">';
echo \Yii::t('admin', 'motion_type_pdf_layout');
echo '</label><div class="col-md-9">';
echo Html::dropDownList(
    'type[pdfLayout]',
    $motionType->pdfLayout,
    \app\views\pdfLayouts\IPDFLayout::getClasses(),
    ['id' => 'pdfLayout', 'class' => 'form-control']
);
echo '</div></div>';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typeMotionPrefix">';
echo \Yii::t('admin', 'motion_type_title_prefix');
echo '</label><div class="col-md-2">';
$options = ['class' => 'form-control', 'id' => 'typeMotionPrefix', 'placeholder' => 'A'];
echo Html::textInput('type[motionPrefix]', $motionType->motionPrefix, $options);
echo '</div></div>';


echo '<h3>' . \Yii::t('admin', 'motion_type_perm') . '</h3>';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typePolicyMotions">';
echo \Yii::t('admin', 'motion_type_perm_motion');
echo '</label><div class="col-md-9">';
echo Html::dropDownList(
    'type[policyMotions]',
    $motionType->policyMotions,
    $policies,
    ['id' => 'typePolicyMotions', 'class' => 'form-control']
);
echo '</div></div>';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typePolicyAmendments">';
echo \Yii::t('admin', 'motion_type_perm_amend');
echo '</label><div class="col-md-9">';
echo Html::dropDownList(
    'type[policyAmendments]',
    $motionType->policyAmendments,
    $policies,
    ['id' => 'typePolicyAmendments', 'class' => 'form-control']
);
echo '</div></div>';

echo '<div class="form-group checkbox" id="typeAmendSinglePara">';
echo '<div class="checkbox col-md-9 col-md-offset-3"><label>
      <input type="checkbox" name="type[amendSinglePara]"';
if (!$motionType->amendmentMultipleParagraphs) {
    echo ' checked';
}
echo '> ' . \Yii::t('admin', 'motion_type_amend_singlep') . '
    </label></div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typePolicyComments">';
echo \Yii::t('admin', 'motion_type_perm_comment');
echo '</label><div class="col-md-9">';
echo Html::dropDownList(
    'type[policyComments]',
    $motionType->policyComments,
    $policies,
    ['id' => 'typePolicyComments', 'class' => 'form-control']
);
echo '</div></div>';


echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typePolicySupport">';
echo \Yii::t('admin', 'motion_type_perm_supp');
echo '</label><div class="col-md-9">';
echo Html::dropDownList(
    'type[policySupport]',
    $motionType->policySupport,
    $policies,
    ['id' => 'typePolicySupport', 'class' => 'form-control']
);
echo '</div></div>';


echo '<h3>' . \Yii::t('admin', 'motion_type_deadline') . '</h3>';

$deadlineMotions = Tools::dateSql2bootstraptime($motionType->deadlineMotions);
echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typeDeadlineMotions">';
echo 'Anträge';
echo '</label><div class="col-md-9">';
echo '<div class="input-group date" id="typeDeadlineMotionsHolder">';
echo '<input id="typeDeadlineMotions" type="text" class="form-control" name="type[deadlineMotions]" ';
echo 'value="' . Html::encode($deadlineMotions) . '" data-locale="' . Html::encode($locale) . '">';
echo '<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>';
echo '</div>';
echo '</div></div>';

$deadlineAmendments = Tools::dateSql2bootstraptime($motionType->deadlineAmendments);
echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typeDeadlineAmendments">';
echo 'ÄA-Antragsschluss';
echo '</label><div class="col-md-9">';
echo '<div class="input-group date" id="typeDeadlineAmendmentsHolder">';
echo '<input id="typeDeadlineAmendments" type="text" class="form-control" name="type[deadlineAmendments]" ';
echo 'value="' . Html::encode($deadlineAmendments) . '" data-locale="' . Html::encode($locale) . '">';
echo '<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>';
echo '</div>';
echo '</div></div>';


echo '<h3>' . \Yii::t('admin', 'motion_type_initiator') . '</h3>';


echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typeInitiatorForm">';
echo 'Formular';
echo '</label><div class="col-md-9">';
echo '<select name="type[initiatorForm]" class="form-control" id="typeInitiatorForm">';
foreach (\app\models\initiatorForms\IInitiatorForm::getImplementations() as $formId => $formClass) {
    echo '<option value="' . Html::encode($formId) . '" ';
    echo 'data-has-supporters="' . ($formClass::hasSupporters() ? 1 : 0) . '"';
    if ($motionType->initiatorForm == $formId) {
        echo ' selected';
    }
    echo '>' . Html::encode($formClass::getTitle()) . '</option>';
}
echo '</select>';
echo '</div></div>';


echo '<div class="form-group">';
echo '<div class="col-md-3 control-label label">' . 'E-Mail-Angabe';
echo '</div><div class="col-md-9 contactDetails contactEMail">';
$options = [
    ConsultationMotionType::CONTACT_NA       => 'Nicht abfragen',
    ConsultationMotionType::CONTACT_OPTIONAL => 'Freiwillig',
    ConsultationMotionType::CONTACT_REQUIRED => 'Benötigt',
];
echo Html::radioList('type[contactEmail]', $motionType->contactEmail, $options, ['class' => 'form-control']);
echo '</div></div>';


echo '<div class="form-group">';
echo '<div class="col-md-3 control-label label">' . 'Telefon-Angabe';
echo '</div><div class="col-md-9 contactDetails contactPhone">';
$options = [
    ConsultationMotionType::CONTACT_NA       => 'Nicht abfragen',
    ConsultationMotionType::CONTACT_OPTIONAL => 'Freiwillig',
    ConsultationMotionType::CONTACT_REQUIRED => 'Benötigt',
];
echo Html::radioList('type[contactPhone]', $motionType->contactPhone, $options, ['class' => 'form-control']);
echo '</div></div>';


$curForm = $motionType->getMotionInitiatorFormClass();

echo '<div class="form-group" id="typeMinSupportersRow">';
echo '<label class="col-md-3 control-label" for="typeMinSupporters">';
echo 'Unterstützer*innen';
echo '</label><div class="col-md-2">';
echo '<input type="number" name="initiator[minSupporters]" class="form-control" id="typeMinSupporters"';
if (is_subclass_of($curForm, \app\models\initiatorForms\DefaultFormBase::class)) {
    /** @var \app\models\initiatorForms\DefaultFormBase $curForm */
    echo ' value="' . Html::encode($curForm->getMinNumberOfSupporters()) . '"';
}
echo '></div></div>';

echo '<div class="form-group checkbox" id="typeAllowMoreSupporters">';
echo '<div class="checkbox col-md-9 col-md-offset-3"><label>
      <input type="checkbox" name="initiator[allowMoreSupporters]"';
if ($curForm->allowMoreSupporters()) {
    echo ' checked';
}
echo '> ' . 'Auch mehr Unterstützer*innen zulassen' . '
    </label></div>';
echo '</div>';

echo '<div class="form-group checkbox" id="typeHasOrgaRow">';
echo '<div class="checkbox col-md-9 col-md-offset-3"><label>
      <input type="checkbox" name="initiator[hasOrganizations]"';
if (is_subclass_of($curForm, \app\models\initiatorForms\DefaultFormBase::class)) {
    /** @var \app\models\initiatorForms\DefaultFormBase $curForm */
    if ($curForm->hasOrganizations()) {
        echo ' checked';
    }
}
echo '> Gremium/Organisation auch bei natürlichen Personen abfragen
    </label></div>';
echo '</div>';


echo '<div class="submitRow"><button type="submit" name="save" class="btn btn-primary">Speichern</button></div>';

echo '</div>';


echo '<h2 class="green">Antrags-Abschnitte</h2>';
echo '<div class="content">';

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

    echo '</div><div class="bottomrow"><div class="positionRow">';

    echo '<div>' . \Yii::t('admin', 'motion_type_pos') . '</div>';
    echo '<label class="positionSection">';
    echo Html::radio($sectionName . '[positionRight]', ($section->positionRight != 1), ['value' => 0]);
    echo ' ' . \Yii::t('admin', 'motion_type_pos_left') . '</label><br>';
    echo '<label class="positionSection">';
    echo Html::radio($sectionName . '[positionRight]', ($section->positionRight == 1), ['value' => 1]);
    echo ' ' . \Yii::t('admin', 'motion_type_pos_right') . '</label><br>';

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
    $val = ConsultationSettingsMotionSection::COMMENTS_MOTION;
    echo Html::radio($sectionName . '[hasComments]', ($section->hasComments == $val), ['value' => $val]);
    echo ' Gesamter Antrag</label> ';

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

    $newRow   = new TabularDataType(['rowId' => '#NEWDATA#', 'type' => TabularDataType::TYPE_STRING, 'title' => '']);
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

echo '<div class="submitRow"><button type="submit" name="save" class="btn btn-primary">' .
    \Yii::t('base', 'save') . '</button></div>';

$layout->addOnLoadJS('$.AntragsgruenAdmin.motionTypeEdit();');

echo '</div>';
echo Html::endForm();

echo '<ul style="display: none;" id="sectionTemplate">';
$renderSection(new ConsultationSettingsMotionSection());
echo '</ul>';


echo '<br><br><div class="deleteTypeOpener content">';
echo '<a href="#">' . \Yii::t('admin', 'motion_type_del_caller') . '</a>';
echo '</div>';
echo Html::beginForm($myUrl, 'post', ['class' => 'deleteTypeForm hidden content']);

if ($motionType->isDeletable()) {
    echo '<div class="submitRow"><button type="submit" name="delete" class="btn btn-danger">' .
        \Yii::t('admin', 'motion_type_del_btn') . '</button></div>';
} else {
    echo '<p class="notDeletable">' . \Yii::t('admin', 'motion_type_not_deletable') . '</p>';
}

echo Html::endForm();
