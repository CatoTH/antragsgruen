<?php

use app\components\{DateTools, Tools, HTMLTools};
use app\models\db\ConsultationMotionType;
use app\models\forms\DeadlineForm;
use yii\helpers\Html;

/**
 * @var ConsultationMotionType $motionType
 * @var string $locale
 */

$deadlineForm = DeadlineForm::createFromMotionType($motionType);

$simpleDeadlineMotions    = Tools::dateSql2bootstraptime($deadlineForm->getSimpleMotionsDeadline());
$simpleDeadlineAmendments = Tools::dateSql2bootstraptime($deadlineForm->getSimpleAmendmentsDeadline());

?>
<section aria-labelledby="motionDeadlinesTitle">
<h2 class="green" id="motionDeadlinesTitle"><?= Yii::t('admin', 'motion_deadlines_head') ?></h2>
<div class="content">

<div>
    <?= HTMLTools::labeledCheckbox(
        'deadlines[formtypeComplex]',
        Yii::t('admin', 'motion_deadline_complex'),
        !$deadlineForm->isSimpleConfiguration(),
        'deadlineFormTypeComplex'
    ) ?>
</div>

<div class="stdTwoCols deadlineTypeSimple hideForAmendmentsOnly">
    <label class="leftColumn" for="typeSimpleDeadlineMotions">
        <?= Yii::t('admin', 'motion_sdeadline_mot') ?>
    </label>
    <div class="rightColumn">
        <div class="input-group date datetimepicker" id="typeDeadlineMotionsHolder">
            <input id="typeSimpleDeadlineMotions" type="text" class="form-control"
                   name="deadlines[motionsSimple]" autocomplete="off"
                   value="<?= Html::encode($simpleDeadlineMotions) ?>" data-locale="<?= Html::encode($locale) ?>">
            <span class="input-group-addon" aria-hidden="true"><span class="glyphicon glyphicon-calendar"></span></span>
        </div>
    </div>
</div>

<div class="stdTwoCols deadlineTypeSimple">
    <label class="leftColumn" for="typeSimpleDeadlineAmendments">
        <?= Yii::t('admin', 'motion_sdeadline_amend'); ?>
    </label>
    <div class="rightColumn">
        <div class="input-group date datetimepicker" id="typeDeadlineAmendmentsHolder">
            <input id="typeSimpleDeadlineAmendments" type="text" class="form-control"
                   name="deadlines[amendmentsSimple]" autocomplete="off"
                   value="<?= Html::encode($simpleDeadlineAmendments) ?>" data-locale="<?= Html::encode($locale) ?>">
            <span class="input-group-addon" aria-hidden="true"><span class="glyphicon glyphicon-calendar"></span></span>
        </div>
    </div>
</div>

<div class="hidden deadlineRowTemplate">
    <?php
    $data = ['start' => null, 'end' => null, 'title' => null];
    echo $this->render('_deadline_row', ['locale' => $locale, 'type' => 'TEMPLATE', 'data' => $data]);
    ?>
</div>

<?php
$type = ConsultationMotionType::DEADLINE_MOTIONS;
?>
<section class="deadlineTypeComplex deadlineHolder motionDeadlines" data-type="<?= $type ?>">
    <h3 class="h4"><?= Yii::t('admin', 'motion_cdeadline_mot') ?>:</h3>
    <div class="deadlineList">
        <?php
        foreach ($motionType->getDeadlinesByType($type) as $deadline) {
            echo $this->render('_deadline_row', ['locale' => $locale, 'type' => $type, 'data' => $deadline]);
        }
        ?>
    </div>
    <button type="button" class="btn btn-link btn-xs deadlineAdder">
        <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
        <?= Yii::t('admin', 'motion_cdeadline_add') ?>
    </button>
</section>

<?php
$type = ConsultationMotionType::DEADLINE_AMENDMENTS;
?>
<div class="deadlineTypeComplex deadlineHolder amendmentDeadlines" data-type="<?= $type ?>">
    <h3 class="h4"><?= Yii::t('admin', 'motion_cdeadline_amend') ?>:</h3>
    <div class="deadlineList">
        <?php
        foreach ($motionType->getDeadlinesByType($type) as $deadline) {
            echo $this->render('_deadline_row', ['locale' => $locale, 'type' => $type, 'data' => $deadline]);
        }
        ?>
    </div>
    <button type="button" class="btn btn-link btn-xs deadlineAdder">
        <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
        <?= Yii::t('admin', 'motion_cdeadline_add') ?>
    </button>
</div>

<?php
$type = ConsultationMotionType::DEADLINE_MERGING;
?>
<div class="deadlineTypeComplex deadlineHolder mergingDeadlines" data-type="<?= $type ?>">
    <h3 class="h4"><?= Yii::t('admin', 'motion_cdeadline_merge') ?>:</h3>
    <div class="deadlineList">
        <?php
        foreach ($motionType->getDeadlinesByType($type) as $deadline) {
            echo $this->render('_deadline_row', ['locale' => $locale, 'type' => $type, 'data' => $deadline]);
        }
        ?>
    </div>
    <button type="button" class="btn btn-link btn-xs deadlineAdder">
        <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
        <?= Yii::t('admin', 'motion_cdeadline_add') ?>
    </button>
</div>

<?php
$type = ConsultationMotionType::DEADLINE_COMMENTS;
?>
<div class="deadlineTypeComplex deadlineHolder commentDeadlines" data-type="<?= $type ?>">
    <h3 class="h4"><?= Yii::t('admin', 'motion_cdeadline_com') ?>:</h3>
    <div class="deadlineList">
        <?php
        foreach ($motionType->getDeadlinesByType($type) as $deadline) {
            echo $this->render('_deadline_row', ['locale' => $locale, 'type' => $type, 'data' => $deadline]);
        }
        ?>
    </div>
    <button type="button" class="btn btn-link btn-xs deadlineAdder">
        <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
        <?= Yii::t('admin', 'motion_cdeadline_add') ?>
    </button>
</div>

<div class="deadlineTypeComplex">
    <?= HTMLTools::labeledCheckbox(
        'activateDeadlineDebugMode',
        Yii::t('admin', 'motion_deadline_debug'),
        DateTools::isDeadlineDebugModeActive($motionType->getConsultation()),
        'deadlineDebugMode'
    ) ?>
</div>

</div>
</section>
