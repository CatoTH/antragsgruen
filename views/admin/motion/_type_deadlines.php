<?php

use app\components\Tools;
use app\models\db\ConsultationMotionType;
use app\models\forms\DeadlineForm;
use yii\helpers\Html;

/**
 * @var ConsultationMotionType $motionType
 * @var string $locale
 */

$deadlineForm = DeadlineForm::createFromMotionType($motionType);

$simpleDeadlineMotions = Tools::dateSql2bootstraptime($deadlineForm->getSimpleMotionsDeadline());
$simpleDeadlineAmendments = Tools::dateSql2bootstraptime($deadlineForm->getSimpleAmendmentsDeadline());

?>
<h3><?= \Yii::t('admin', 'motion_type_deadline') ?></h3>
<div class="form-group">
    <label class="col-md-4 control-label" for="typeSimpleDeadlineMotions">
        <?= \Yii::t('admin', 'motion_type_deadline_mot') ?>
    </label>
    <div class="col-md-8">
        <div class="input-group date" id="typeDeadlineMotionsHolder">
            <input id="typeSimpleDeadlineMotions" type="text" class="form-control" name="type[deadlineMotions]"
                   value="<?= Html::encode($simpleDeadlineMotions) ?>" data-locale="<?= Html::encode($locale) ?>">
            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
        </div>
    </div>
</div>

<div class="form-group">
    <label class="col-md-4 control-label" for="typeSimpleDeadlineAmendments">
        <?= \Yii::t('admin', 'motion_type_amend_deadline'); ?>
    </label>
    <div class="col-md-8">
        <div class="input-group date" id="typeDeadlineAmendmentsHolder">
            <input id="typeSimpleDeadlineAmendments" type="text" class="form-control" name="type[deadlineAmendments]"
                   value="<?= Html::encode($simpleDeadlineAmendments) ?>" data-locale="<?= Html::encode($locale) ?>">
            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
        </div>
    </div>
</div>
