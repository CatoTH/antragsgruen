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
$layout->loadSelectize();
$layout->addAMDModule('backend/MotionTypeEdit');

$myUrl = UrlHelper::createUrl(['/admin/motion-type/type', 'motionTypeId' => $motionType->id]);

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

echo $controller->showErrors();

if ($motionType->amendmentsOnly) {
    echo $this->render('_amendments_only_motions', ['motionType' => $motionType]);
}
echo $this->render('_names', ['motionType' => $motionType]);
echo $this->render('_policy', ['motionType' => $motionType]);
echo $this->render('_deadlines', ['motionType' => $motionType, 'locale' => $locale]);
echo $this->render('_initiator', ['motionType' => $motionType]);
echo $this->render('_pdf', ['motionType' => $motionType]);

?>
    <div class="content">
        <div class="submitRow">
            <button type="submit" name="save" class="btn btn-primary">
                <?= Yii::t('admin', 'save') ?>
            </button>
        </div>
    </div>

    <br><br>

    <h1 class="green"><?= Yii::t('admin', 'motion_section_title') ?></h1>
    <div class="content">

        <ul id="sectionsList">
            <?php
            foreach ($motionType->motionSections as $section) {
                echo $this->render('_sections', ['section' => $section]);
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
        echo $this->render('_sections', ['section' => $template])
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
