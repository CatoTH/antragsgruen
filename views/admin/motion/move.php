<?php

use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\ConsultationAgendaItem;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\forms\MotionMover $form
 */

$motion = $form->getMotion();

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$layout       = $controller->layoutParams;
$consultation = $controller->consultation;

$this->title = Yii::t('admin', 'motion_edit_title') . ': ' . $motion->getTitleWithPrefix();
$layout->addBreadcrumb(Yii::t('admin', 'bread_list'), UrlHelper::createUrl('admin/motion-list/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_motion'), UrlHelper::createUrl(['admin/motion/update', 'motionId' => $motion->id]));
$layout->addBreadcrumb(Yii::t('admin', 'bread_move'));

$layout->addCSS('css/backend.css');

echo '<h1>' . Yii::t('admin', 'motion_move_title') . '</h1>';

$myUrl = UrlHelper::createUrl(['admin/motion/move', 'motionId' => $motion->id]);
echo Html::beginForm($myUrl, 'post', [
    'data-antragsgruen-widget' => 'backend/MoveMotion',
    'data-check-backend'       => UrlHelper::createUrl(['/admin/motion/move-check', 'motionId' => $motion->id]),
    'class'                    => 'adminMoveForm form-horizontal',
]);

$targetConsultations = $form->getConsultationTargets();

?>
    <div class="content form-horizontal">

        <div class="form-group">
            <label class="col-md-3 control-label"><?= Yii::t('admin', 'motion_move_op') ?>:</label>
            <div class="col-md-9">
                <label>
                    <input type="radio" name="operation" value="copy" required>
                    <?= Yii::t('admin', 'motion_move_op_copy') ?>
                    <div class="checkboxSubtitle"><?= Yii::t('admin', 'motion_move_op_copyh') ?></div>
                </label>
                <label>
                    <input type="radio" name="operation" value="move" required>
                    <?= Yii::t('admin', 'motion_move_op_move') ?>
                    <div class="checkboxSubtitle"><?= Yii::t('admin', 'motion_move_op_moveh') ?></div>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="col-md-3 control-label"><?= Yii::t('admin', 'motion_move_target') ?>:</label>
            <div class="col-md-9">
                <label>
                    <input type="radio" name="target" value="agenda" required>
                    <?= Yii::t('admin', 'motion_move_target_agenda') ?>
                </label><br>
                <label>
                    <input type="radio" name="target" value="consultation" required>
                    <?= Yii::t('admin', 'motion_move_target_con') ?>
                </label><br>
            </div>
        </div>

        <div class="form-group moveToAgendaItem">
            <label class="col-md-3 control-label"><?= Yii::t('admin', 'motion_move_agenda_item') ?>:</label>
            <div class="col-md-9">
                <?php
                $agendaItems = ConsultationAgendaItem::getSortedFromConsultation($consultation);
                if (count($agendaItems) > 0) {
                    $options    = ['id' => 'agendaItemId' . $consultation->id, 'class' => 'stdDropdown'];
                    $selections = [];
                    foreach ($agendaItems as $item) {
                        $selections[$item->id] = $item->title;
                    }

                    echo Html::dropDownList('agendaItem[' . $consultation->id . ']', $motion->agendaItemId, $selections, $options);
                } else {
                    echo '<em> ' . Yii::t('admin', 'motion_move_agenda_none') . '</em>';
                }
                ?>
            </div>
        </div>

        <div class="form-group moveToConsultationItem">
            <label class="col-md-3 control-label"><?= Yii::t('admin', 'motion_move_con') ?>:</label>
            <div class="col-md-9">
                <?php
                if (count($targetConsultations) > 0) {
                    $selections = [];
                    foreach ($targetConsultations as $targetCon) {
                        $selections[$targetCon->id] = $targetCon->title;
                    }

                    echo Html::dropDownList('consultation', '', $selections, ['id' => 'consultationId', 'class' => 'stdDropdown']);
                } else {
                    echo '<em> ' . Yii::t('admin', 'motion_move_con_none') . '</em>';
                }
                ?>
            </div>
        </div>
        <?php
        foreach ($targetConsultations as $targetCon) {
            ?>
            <div class="form-group moveToMotionTypeId moveToMotionTypeId<?= $targetCon->id ?>">
                <label class="col-md-3 control-label"><?= Yii::t('admin', 'motion_move_type') ?>:</label>
                <div class="col-md-9">
                    <?php
                    $options    = ['id' => 'motionType' . $targetCon->id, 'class' => 'stdDropdown'];
                    $selections = [];
                    foreach ($form->getCompatibleMotionTypes($targetCon) as $motionType) {
                        $selections[$motionType->id] = $motionType->titlePlural;
                    }

                    echo Html::dropDownList('motionType[' . $targetCon->id . ']', '', $selections, $options);
                    ?>
                </div>
            </div>
            <?php
        }
        ?>

        <div class="form-group">
            <label class="col-md-3 control-label" for="motionTitlePrefix"><?= Yii::t('admin', 'motion_move_prefix') ?>:</label>
            <div class="col-md-4"><?php
                echo Html::textInput('titlePrefix', $motion->titlePrefix, [
                    'class'       => 'form-control',
                    'id'          => 'motionTitlePrefix',
                    'placeholder' => Yii::t('admin', 'motion_prefix_hint'),
                    'required'    => 'required',
                ]);
                ?>
                <small><?= Yii::t('admin', 'motion_prefix_unique') ?></small>
            </div>
            <div class="col-md-5">
                <span class="prefixAlreadyTaken hidden"><?= Yii::t('admin', 'motion_prefix_collision') ?></span>
            </div>
        </div>
    </div>
    <div class="saveholder">
        <button type="submit" name="move" class="btn btn-primary save"><?= Yii::t('admin', 'save') ?></button>
    </div>
<?php
echo Html::endForm();
