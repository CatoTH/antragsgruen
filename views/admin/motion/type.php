<?php

use app\components\HTMLTools;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\supportTypes\CollectBeforePublish;
use app\models\supportTypes\DefaultTypeBase;
use app\models\supportTypes\ISupportType;
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
$layout->loadFuelux();
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

echo Html::beginForm($myUrl, 'post', ['class' => 'adminTypeForm form-horizontal fuelux']);

echo '<div class="content">';

echo $controller->showErrors();

?>
    <h3><?= \Yii::t('admin', 'motion_type_names') ?></h3>
    <div class="form-group">
        <label class="col-md-4 control-label" for="typeTitleSingular">
            <?= \Yii::t('admin', 'motion_type_singular') ?>
        </label>
        <div class="col-md-8"><?php
            $options = [
                'class'       => 'form-control',
                'id'          => 'typeTitleSingular',
                'placeholder' => \Yii::t('admin', 'motion_type_singular_pl'),
            ];
            echo Html::textInput('type[titleSingular]', $motionType->titleSingular, $options);
            ?></div>
    </div>

    <div class="form-group">
        <label class="col-md-4 control-label" for="typeTitlePlural">
            <?= \Yii::t('admin', 'motion_type_plural') ?>
        </label>
        <div class="col-md-8">
            <?php
            $options = [
                'class'       => 'form-control',
                'id'          => 'typeTitlePlural',
                'placeholder' => \Yii::t('admin', 'motion_type_plural_pl'),
            ];
            echo Html::textInput('type[titlePlural]', $motionType->titlePlural, $options);
            ?>
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 control-label" for="typeCreateTitle">
            <?= \Yii::t('admin', 'motion_type_create_title') ?>
        </label>
        <div class="col-md-8">
            <?php
            $options = [
                'class'       => 'form-control',
                'id'          => 'typeCreateTitle',
                'placeholder' => \Yii::t('admin', 'motion_type_create_placeh')
            ];
            echo HTMLTools::smallTextarea('type[createTitle]', $options, $motionType->createTitle);
            ?>
        </div>
    </div>
    <div class="form-group checkbox checkboxNoPadding" id="typeCreateSidebar">
        <div class="checkbox col-md-8 col-md-offset-4">
            <?php
            echo HTMLTools::fueluxCheckbox(
                'type[sidebarCreateButton]',
                \Yii::t('admin', 'motion_type_create_sidebar'),
                $motionType->sidebarCreateButton
            );
            ?>
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 control-label" for="typeMotionPrefix">
            <?= \Yii::t('admin', 'motion_type_title_prefix') ?>
        </label>
        <div class="col-md-2">
            <?php
            $options = ['class' => 'form-control', 'id' => 'typeMotionPrefix', 'placeholder' => 'A'];
            echo Html::textInput('type[motionPrefix]', $motionType->motionPrefix, $options);
            ?>
        </div>
    </div>
<?php


echo $this->render('_type_policy', ['motionType' => $motionType]);
echo $this->render('_type_deadlines', ['motionType' => $motionType, 'locale' => $locale]);

?>
    <h3><?= \Yii::t('admin', 'motion_type_initiator') ?></h3>

    <div class="form-group">
        <label class="col-md-4 control-label" for="typeSupportType">
            <?= \Yii::t('admin', 'motion_type_supp_form') ?>
        </label>
        <div class="col-md-8">
            <?php
            $options = [];
            foreach (ISupportType::getImplementations() as $formId => $formClass) {
                $supporters = ($formClass::hasInitiatorGivenSupporters() || $formClass === CollectBeforePublish::class);
                $options[]  = [
                    'title'      => $formClass::getTitle(),
                    'attributes' => ['data-has-supporters' => ($supporters ? '1' : '0')],
                ];
            }
            echo HTMLTools::fueluxSelectbox(
                'type[supportType]',
                $options,
                $motionType->supportType,
                ['id' => 'typeSupportType'],
                true
            );
            ?>
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4" style="text-align: right;">
            <?= \Yii::t('admin', 'motion_type_contact_name') ?>
        </label>
        <div class="col-md-8 contactDetails contactName">
            <?php
            $options = [
                ConsultationMotionType::CONTACT_NONE     => \Yii::t('admin', 'motion_type_skip'),
                ConsultationMotionType::CONTACT_OPTIONAL => \Yii::t('admin', 'motion_type_optional'),
                ConsultationMotionType::CONTACT_REQUIRED => \Yii::t('admin', 'motion_type_required'),
            ];
            echo Html::radioList('type[contactName]', $motionType->contactName, $options, ['class' => 'form-control']);
            ?>
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4" style="text-align: right;">
            <?= \Yii::t('admin', 'motion_type_email') ?>
        </label>
        <div class="col-md-8 contactDetails contactEMail">
            <?php
            $options = [
                ConsultationMotionType::CONTACT_NONE     => \Yii::t('admin', 'motion_type_skip'),
                ConsultationMotionType::CONTACT_OPTIONAL => \Yii::t('admin', 'motion_type_optional'),
                ConsultationMotionType::CONTACT_REQUIRED => \Yii::t('admin', 'motion_type_required'),
            ];
            echo Html::radioList(
                'type[contactEmail]',
                $motionType->contactEmail,
                $options,
                ['class' => 'form-control']
            );
            ?>
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4" style="text-align: right;">
            <?= \Yii::t('admin', 'motion_type_phone') ?>
        </label>
        <div class="col-md-8 contactDetails contactPhone">
            <?php
            $options = [
                ConsultationMotionType::CONTACT_NONE     => \Yii::t('admin', 'motion_type_skip'),
                ConsultationMotionType::CONTACT_OPTIONAL => \Yii::t('admin', 'motion_type_optional'),
                ConsultationMotionType::CONTACT_REQUIRED => \Yii::t('admin', 'motion_type_required'),
            ];
            echo Html::radioList(
                'type[contactPhone]',
                $motionType->contactPhone,
                $options,
                ['class' => 'form-control']
            );
            ?>
        </div>
    </div>

    <div class="form-group" id="typeMinSupportersRow">
        <label class="col-md-4 control-label" for="typeMinSupporters">
            <?= \Yii::t('admin', 'motion_type_supp_min') ?>
        </label>
        <div class="col-md-2">
            <?php
            $curForm = $motionType->getMotionSupportTypeClass();
            echo '<input type="number" name="initiator[minSupporters]" class="form-control" id="typeMinSupporters"';
            if (is_subclass_of($curForm, DefaultTypeBase::class)) {
                /** @var \app\models\supportTypes\DefaultTypeBase $curForm */
                echo ' value="' . Html::encode($curForm->getMinNumberOfSupporters()) . '"';
            }
            echo '>';
            ?>
        </div>
    </div>

    <div class="form-group" id="typeAllowMoreSupporters">
        <div class="checkbox col-md-8 col-md-offset-4">
            <?php
            echo HTMLTools::fueluxCheckbox(
                'initiator[allowMoreSupporters]',
                \Yii::t('admin', 'motion_type_allow_more_supp'),
                $curForm->allowMoreSupporters()
            );
            ?>
        </div>
    </div>

    <div class="form-group" id="typeHasOrgaRow">
        <div class="checkbox col-md-8 col-md-offset-4">
            <?php
            echo HTMLTools::fueluxCheckbox(
                'initiator[hasOrganizations]',
                \Yii::t('admin', 'motion_type_ask_orga'),
                (is_subclass_of($curForm, DefaultTypeBase::class) && $curForm->hasOrganizations())
            );
            ?>
        </div>
    </div>


    <h3><?= \Yii::t('admin', 'motion_type_pdf_layout') ?></h3>

    <div class="form-group">
        <label class="col-sm-4 control-label" for="pdfIntroduction"><?= \Yii::t('admin', 'con_pdf_intro') ?>
            :</label>
        <div class="col-sm-8">
        <textarea name="type[pdfIntroduction]" class="form-control" id="pdfIntroduction"
                  placeholder="<?= Html::encode(\Yii::t('admin', 'con_pdf_intro_place')) ?>"
        ><?= $motionType->getSettingsObj()->pdfIntroduction ?></textarea>
        </div>
    </div>

    <div class="thumbnailedLayoutSelector">
        <?php
        $currValue = ($motionType->texTemplateId ? $motionType->texTemplateId : 'php' . $motionType->pdfLayout);
        foreach ($motionType->getAvailablePDFTemplates() as $lId => $layout) {
            echo '<label class="layout ' . $lId . '">';
            echo Html::radio('pdfTemplate', $lId === $currValue, ['value' => $lId]);
            echo '<span>';
            if ($layout['preview']) {
                echo '<img src="' . Html::encode($layout['preview']) . '" ' .
                    'alt="' . Html::encode($layout['title']) . '" ' .
                    'title="' . Html::encode($layout['title']) . '"></span>';
            } else {
                echo '<span class="placeholder">' . Html::encode($layout['title']) . '</span>';
            }
            echo '</label>';
        }
        ?>
    </div>

    <div class="submitRow">
        <button type="submit" name="save" class="btn btn-primary">
            <?= \Yii::t('admin', 'save') ?>
        </button>
    </div>
<?php


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

?>
    <ul style="display: none;" id="sectionTemplate">
        <?= $this->render('_type_sections', ['section' => new ConsultationSettingsMotionSection()]) ?>
    </ul>

    <br><br>
    <div class="deleteTypeOpener content">
        <button class="btn btn-danger btn-link" type="button">
            <span class="glyphicon glyphicon-trash"></span>
            <?= \Yii::t('admin', 'motion_type_del_caller') ?>
        </button>
    </div>

<?php

echo Html::beginForm($myUrl, 'post', ['class' => 'deleteTypeForm hidden content']);

if ($motionType->isDeletable()) {
    echo '<div class="submitRow"><button type="submit" name="delete" class="btn btn-danger">';
    echo '<span class="glyphicon glyphicon-trash"></span>';
    echo \Yii::t('admin', 'motion_type_del_btn') . '</button></div>';
} else {
    echo '<p class="notDeletable">' . \Yii::t('admin', 'motion_type_not_deletable') . '</p>';
}

echo Html::endForm();
