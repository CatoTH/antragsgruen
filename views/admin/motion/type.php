<?php

use app\components\HTMLTools;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\supportTypes\CollectBeforePublish;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var ConsultationMotionType $motionType
 * @var string $supportCollPolicyWarning
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = \Yii::t('admin', 'motion_type_edit');
$layout->addBreadcrumb(\Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(\Yii::t('admin', 'bread_types'));

$layout->addCSS('css/backend.css');
$layout->loadSortable();
$layout->loadDatepicker();
$layout->addAMDModule('backend/MotionTypeEdit');

$myUrl = UrlHelper::createUrl(['admin/motion/type', 'motionTypeId' => $motionType->id]);

$locale = Tools::getCurrentDateLocale();


echo '<h1>' . \Yii::t('admin', 'motion_type_edit') . '</h1>';

if ($supportCollPolicyWarning) {
    echo '<div class="adminTypePolicyFix alert alert-info alert-dismissible" role="alert">
<button type="button" class="close" data-dismiss="alert"
aria-label="Close"><span aria-hidden="true">&times;</span></button>' .
        Html::beginForm('', 'post', ['id' => 'policyFixForm']) . \Yii::t('admin', 'support_coll_policy_warning') .
        '<div class="saveholder"><button type="submit" name="supportCollPolicyFix" class="btn btn-primary">' .
        \Yii::t('admin', 'support_coll_policy_fix') . '</button></div>' .
        Html::endForm() . '</div>';
}

echo Html::beginForm($myUrl, 'post', ['class' => 'adminTypeForm form-horizontal']);

echo '<div class="content">';

echo $controller->showErrors();


echo '<h3>' . \Yii::t('admin', 'motion_type_names') . '</h3>';

echo '<div class="form-group">';
echo '<label class="col-md-4 control-label" for="typeTitleSingular">';
echo \Yii::t('admin', 'motion_type_singular');
echo '</label><div class="col-md-8">';
$options = [
    'class'       => 'form-control',
    'id'          => 'typeTitleSingular',
    'placeholder' => \Yii::t('admin', 'motion_type_singular_pl'),
];
echo Html::textInput('type[titleSingular]', $motionType->titleSingular, $options);
echo '</div></div>';

echo '<div class="form-group">';
echo '<label class="col-md-4 control-label" for="typeTitlePlural">';
echo \Yii::t('admin', 'motion_type_plural');
echo '</label><div class="col-md-8">';
$options = [
    'class'       => 'form-control',
    'id'          => 'typeTitlePlural',
    'placeholder' => \Yii::t('admin', 'motion_type_plural_pl'),
];
echo Html::textInput('type[titlePlural]', $motionType->titlePlural, $options);
echo '</div></div>';

echo '<div class="form-group">';
echo '<label class="col-md-4 control-label" for="typeCreateTitle">';
echo \Yii::t('admin', 'motion_type_create_title');
echo '</label><div class="col-md-8">';

$options = [
    'class'       => 'form-control',
    'id'          => 'typeCreateTitle',
    'placeholder' => \Yii::t('admin', 'motion_type_create_placeh')
];
echo HTMLTools::smallTextarea('type[createTitle]', $options, $motionType->createTitle);
echo '</div></div>';

echo '<div class="form-group">';
echo '<label class="col-md-4 control-label" for="pdfLayout">';
echo \Yii::t('admin', 'motion_type_pdf_layout');
echo '</label><div class="col-md-8">';
echo Html::dropDownList(
    'pdfTemplate',
    ($motionType->texTemplateId ? $motionType->texTemplateId : 'php' . $motionType->pdfLayout),
    $motionType->getAvailablePDFTemplates(),
    ['id' => 'pdfLayout', 'class' => 'form-control']
);
echo '</div></div>';

echo '<div class="form-group">';
echo '<label class="col-md-4 control-label" for="typeMotionPrefix">';
echo \Yii::t('admin', 'motion_type_title_prefix');
echo '</label><div class="col-md-2">';
$options = ['class' => 'form-control', 'id' => 'typeMotionPrefix', 'placeholder' => 'A'];
echo Html::textInput('type[motionPrefix]', $motionType->motionPrefix, $options);
echo '</div></div>';


echo $this->render('_type_policy', ['motionType' => $motionType]);


/* Deadlines */

echo '<h3>' . \Yii::t('admin', 'motion_type_deadline') . '</h3>';

$deadlineMotions = Tools::dateSql2bootstraptime($motionType->deadlineMotions);
echo '<div class="form-group">';
echo '<label class="col-md-4 control-label" for="typeDeadlineMotions">';
echo \Yii::t('admin', 'motion_type_deadline_mot');
echo '</label><div class="col-md-8">';
echo '<div class="input-group date" id="typeDeadlineMotionsHolder">';
echo '<input id="typeDeadlineMotions" type="text" class="form-control" name="type[deadlineMotions]" ';
echo 'value="' . Html::encode($deadlineMotions) . '" data-locale="' . Html::encode($locale) . '">';
echo '<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>';
echo '</div>';
echo '</div></div>';

$deadlineAmendments = Tools::dateSql2bootstraptime($motionType->deadlineAmendments);
echo '<div class="form-group">';
echo '<label class="col-md-4 control-label" for="typeDeadlineAmendments">';
echo \Yii::t('admin', 'motion_type_amend_deadline');
echo '</label><div class="col-md-8">';
echo '<div class="input-group date" id="typeDeadlineAmendmentsHolder">';
echo '<input id="typeDeadlineAmendments" type="text" class="form-control" name="type[deadlineAmendments]" ';
echo 'value="' . Html::encode($deadlineAmendments) . '" data-locale="' . Html::encode($locale) . '">';
echo '<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>';
echo '</div>';
echo '</div></div>';


echo '<h3>' . \Yii::t('admin', 'motion_type_initiator') . '</h3>';


echo '<div class="form-group">';
echo '<label class="col-md-4 control-label" for="typeSupportType">';
echo \Yii::t('admin', 'motion_type_supp_form');
echo '</label><div class="col-md-8">';
echo '<select name="type[supportType]" class="form-control" id="typeSupportType">';
foreach (\app\models\supportTypes\ISupportType::getImplementations() as $formId => $formClass) {
    $supporters = ($formClass::hasInitiatorGivenSupporters() || $formClass == CollectBeforePublish::class);
    echo '<option value="' . Html::encode($formId) . '" ';
    echo 'data-has-supporters="' . ($supporters ? 1 : 0) . '"';
    if ($motionType->supportType == $formId) {
        echo ' selected';
    }
    echo '>' . Html::encode($formClass::getTitle()) . '</option>';
}
echo '</select>';
echo '</div></div>';


echo '<div class="form-group">';
echo '<label class="col-md-4" style="text-align: right;">' . \Yii::t('admin', 'motion_type_contact_name');
echo '</label><div class="col-md-8 contactDetails contactName">';
$options = [
    ConsultationMotionType::CONTACT_NONE       => \Yii::t('admin', 'motion_type_skip'),
    ConsultationMotionType::CONTACT_OPTIONAL => \Yii::t('admin', 'motion_type_optional'),
    ConsultationMotionType::CONTACT_REQUIRED => \Yii::t('admin', 'motion_type_required'),
];
echo Html::radioList('type[contactName]', $motionType->contactName, $options, ['class' => 'form-control']);
echo '</div></div>';


echo '<div class="form-group">';
echo '<label class="col-md-4" style="text-align: right;">' . \Yii::t('admin', 'motion_type_email');
echo '</label><div class="col-md-8 contactDetails contactEMail">';
$options = [
    ConsultationMotionType::CONTACT_NONE       => \Yii::t('admin', 'motion_type_skip'),
    ConsultationMotionType::CONTACT_OPTIONAL => \Yii::t('admin', 'motion_type_optional'),
    ConsultationMotionType::CONTACT_REQUIRED => \Yii::t('admin', 'motion_type_required'),
];
echo Html::radioList('type[contactEmail]', $motionType->contactEmail, $options, ['class' => 'form-control']);
echo '</div></div>';


echo '<div class="form-group">';
echo '<label class="col-md-4" style="text-align: right;">' . \Yii::t('admin', 'motion_type_phone');
echo '</label><div class="col-md-8 contactDetails contactPhone">';
$options = [
    ConsultationMotionType::CONTACT_NONE       => \Yii::t('admin', 'motion_type_skip'),
    ConsultationMotionType::CONTACT_OPTIONAL => \Yii::t('admin', 'motion_type_optional'),
    ConsultationMotionType::CONTACT_REQUIRED => \Yii::t('admin', 'motion_type_required'),
];
echo Html::radioList('type[contactPhone]', $motionType->contactPhone, $options, ['class' => 'form-control']);
echo '</div></div>';


$curForm = $motionType->getMotionSupportTypeClass();

echo '<div class="form-group" id="typeMinSupportersRow">';
echo '<label class="col-md-4 control-label" for="typeMinSupporters">';
echo \Yii::t('admin', 'motion_type_supp_min');
echo '</label><div class="col-md-2">';
echo '<input type="number" name="initiator[minSupporters]" class="form-control" id="typeMinSupporters"';
if (is_subclass_of($curForm, \app\models\supportTypes\DefaultTypeBase::class)) {
    /** @var \app\models\supportTypes\DefaultTypeBase $curForm */
    echo ' value="' . Html::encode($curForm->getMinNumberOfSupporters()) . '"';
}
echo '></div></div>';

echo '<div class="form-group checkbox" id="typeAllowMoreSupporters">';
echo '<div class="checkbox col-md-8 col-md-offset-4"><label>
      <input type="checkbox" name="initiator[allowMoreSupporters]"';
if ($curForm->allowMoreSupporters()) {
    echo ' checked';
}
echo '> ' . \Yii::t('admin', 'motion_type_allow_more_supp') . '
    </label></div>';
echo '</div>';

echo '<div class="form-group checkbox" id="typeHasOrgaRow">';
echo '<div class="checkbox col-md-8 col-md-offset-4"><label>
      <input type="checkbox" name="initiator[hasOrganizations]"';
if (is_subclass_of($curForm, \app\models\supportTypes\DefaultTypeBase::class)) {
    /** @var \app\models\supportTypes\DefaultTypeBase $curForm */
    if ($curForm->hasOrganizations()) {
        echo ' checked';
    }
}
echo '> ' . \Yii::t('admin', 'motion_type_ask_orga') . '
    </label></div>';
echo '</div>';


echo '<div class="submitRow"><button type="submit" name="save" class="btn btn-primary">' .
    \Yii::t('admin', 'save') . '</button></div>';

echo '</div>';


echo '<h2 class="green">' . \Yii::t('admin', 'motion_section_title') . '</h2>';
echo '<div class="content">';


echo '<ul id="sectionsList">';
foreach ($motionType->motionSections as $section) {
    echo $this->render('_type_sections', ['section' => $section]);
}
echo '</ul>';

echo '<a href="#" class="sectionAdder"><span class="glyphicon glyphicon-plus-sign"></span> ' .
    Yii::t('admin', 'motion_section_add') . '</a>';

echo '<div class="submitRow"><button type="submit" name="save" class="btn btn-primary">' .
    \Yii::t('base', 'save') . '</button></div>';

echo '</div>';
echo Html::endForm();

echo '<ul style="display: none;" id="sectionTemplate">';
echo $this->render('_type_sections', ['section' => new ConsultationSettingsMotionSection()]);
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
