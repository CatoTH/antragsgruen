<?php

use app\components\{HTMLTools, Tools, UrlHelper};
use app\models\db\{ConsultationMotionType, ConsultationSettingsMotionSection};
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var ConsultationMotionType $motionType
 * @var string $supportCollPolicyWarning
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout = $controller->layoutParams;

$this->title = Yii::t('admin', 'motion_type_edit');
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_types'));

$layout->addCSS('css/backend.css');
$layout->loadSortable();
$layout->loadDatepicker();
$layout->addAMDModule('backend/MotionTypeEdit');

$myUrl = UrlHelper::createUrl(['admin/motion/type', 'motionTypeId' => $motionType->id]);

$locale = Tools::getCurrentDateLocale();


echo '<h1>' . Yii::t('admin', 'motion_type_edit') . '</h1>';

if ($supportCollPolicyWarning) {
    ?>
    <div class="adminTypePolicyFix alert alert-info">
        <?= Html::beginForm('', 'post', ['id' => 'policyFixForm']) ?>
        <?= Yii::t('admin', 'support_coll_policy_warning') ?>
        <div class="saveholder">
            <button type="submit" name="supportCollPolicyFix" class="btn btn-primary">
                <?= Yii::t('admin', 'support_coll_policy_fix') ?>
            </button>
        </div>
        <?= Html::endForm() ?>
    </div>
    <?php
}

$formClasses = ['adminTypeForm', 'form-horizontal'];
if ($motionType->amendmentsOnly) {
    $formClasses[] = 'amendmentsOnly';
}
echo Html::beginForm($myUrl, 'post', ['class' => implode(' ', $formClasses)]);

echo '<div class="content">';

echo $controller->showErrors();

if ($motionType->amendmentsOnly) {
    echo $this->render('_type_amendments_only_motions', ['motionType' => $motionType]);
}
echo $this->render('_type_names', ['motionType' => $motionType]);
echo $this->render('_type_policy', ['motionType' => $motionType]);
echo $this->render('_type_deadlines', ['motionType' => $motionType, 'locale' => $locale]);
echo $this->render('_type_initiator', ['motionType' => $motionType]);

$supportSett = $motionType->getMotionSupportTypeClass()->getSettingsObj();
?>

    <h2 class="h3"><?= Yii::t('admin', 'motion_type_pdf_layout') ?></h2>

    <div class="adminTwoCols">
        <label class="leftColumn" for="pdfIntroduction">
            <?= Yii::t('admin', 'con_pdf_intro') ?>:
        </label>
        <div class="rightColumn">
        <textarea name="type[pdfIntroduction]" class="form-control" id="pdfIntroduction"
                  placeholder="<?= Html::encode(Yii::t('admin', 'con_pdf_intro_place')) ?>"
        ><?= $motionType->getSettingsObj()->pdfIntroduction ?></textarea>
        </div>
    </div>

    <div class="adminTwoCols" id="typeMaxPdfSupportersRow">
        <label class="leftColumn" for="typeMaxPdfSupporters">
            <?= Yii::t('admin', 'motion_type_supp_max_pdf') ?>:
            <?= HTMLTools::getTooltipIcon(Yii::t('admin', 'motion_type_supp_max_pdfd')) ?>
        </label>
        <div class="rightColumn">
            <input type="number" name="maxPdfSupporters" class="form-control" id="typeMaxPdfSupporters"
                   value="<?= Html::encode($supportSett->maxPdfSupporters !== null ? $supportSett->maxPdfSupporters : '') ?>">
        </div>
    </div>

    <fieldset class="thumbnailedLayoutSelector">
        <legend class="sr-only"><?= Yii::t('admin', 'motion_type_pdf_layout') ?></legend>
        <?php
        $currValue = ($motionType->texTemplateId ? $motionType->texTemplateId : 'php' . $motionType->pdfLayout);
        foreach (\app\views\pdfLayouts\IPDFLayout::getAvailableClassesWithLatex() as $lId => $layout) {
            echo '<label class="layout ' . $lId . '">';
            echo Html::radio('pdfTemplate', $lId === $currValue, ['value' => $lId]);
            echo '<span>';
            if ($layout['preview']) {
                echo '<img src="' . Html::encode($layout['preview']) . '" ' .
                     'alt="' . Html::encode($layout['title']) . '" ' .
                     'title="' . Html::encode($layout['title']) . '"></span>';
                echo '<span class="sr-only">' . Html::encode($layout['title']) . '</span>';
            } else {
                echo '<span class="placeholder">' . Html::encode($layout['title']) . '</span>';
            }
            echo '</label>';
        }
        ?>
    </fieldset>

    <div class="submitRow">
        <button type="submit" name="save" class="btn btn-primary">
            <?= Yii::t('admin', 'save') ?>
        </button>
    </div>
<?php


echo '</div>';

?>
    <h2 class="green"><?= Yii::t('admin', 'motion_section_title') ?></h2>
    <div class="content">

        <ul id="sectionsList">
            <?php
            foreach ($motionType->motionSections as $section) {
                echo $this->render('_type_sections', ['section' => $section]);
            }
            ?>
        </ul>

        <button type="button" class="sectionAdder btn btn-link" aria-label="<?= Yii::t('admin', 'motion_section_add') ?>">
            <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
            <?= Yii::t('admin', 'motion_section_add') ?>
        </button>

        <div class="submitRow">
            <button type="submit" name="save" class="btn btn-primary"><?= Yii::t('base', 'save') ?></button>
        </div>
    </div>

<?= Html::endForm(); // adminTypeForm       ?>

    <ul style="display: none;" id="sectionTemplate">
        <?php
        $template = new ConsultationSettingsMotionSection();
        $template->hasComments = ConsultationSettingsMotionSection::COMMENTS_NONE;
        echo $this->render('_type_sections', ['section' => $template])
        ?>
    </ul>

    <br><br>
    <div class="deleteTypeOpener content">
        <button class="btn btn-danger btn-link" type="button">
            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
            <?= Yii::t('admin', 'motion_type_del_caller') ?>
        </button>
    </div>

<?php

echo Html::beginForm($myUrl, 'post', ['class' => 'deleteTypeForm hidden content']);

if ($motionType->isDeletable()) {
    ?>
    <div class="submitRow">
        <button type="submit" name="delete" class="btn btn-danger">
            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
            <?= Yii::t('admin', 'motion_type_del_btn') ?>
        </button>
    </div>
    <?php
} else {
    echo '<p class="notDeletable">' . Yii::t('admin', 'motion_type_not_deletable') . '</p>';
}

echo Html::endForm();
