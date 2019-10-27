<?php

use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\ConsultationAgendaItem;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var Motion $motion
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$layout       = $controller->layoutParams;
$consultation = $controller->consultation;

$this->title = Yii::t('admin', 'motion_edit_title') . ': ' . $motion->getTitleWithPrefix();
$layout->addBreadcrumb(Yii::t('admin', 'bread_list'), UrlHelper::createUrl('admin/motion-list/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_motion'), UrlHelper::createUrl(['admin/motion/update', 'motionId' => $motion->id]));
$layout->addBreadcrumb(Yii::t('admin', 'bread_move'));

$layout->loadFuelux();
$layout->addCSS('css/backend.css');

echo '<h1>' . Yii::t('admin', 'motion_move_title') . '</h1>';

$myUrl = UrlHelper::createUrl(['admin/motion/move', 'motionId' => $motion->id]);
echo Html::beginForm($myUrl, 'post', [
    'data-antragsgruen-widget' => 'backend/MoveMotion',
    'class'                    => 'adminMoveForm form-horizontal fuelux',
]);

?>
    <div class="content fuelux form-horizontal">

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
        </div>

        <div class="form-group">
            <label class="col-md-3 control-label"><?= Yii::t('admin', 'motion_move_target') ?>:</label>
            <label>
                <input type="radio" name="target" value="agenda" required>
                <?= Yii::t('admin', 'motion_move_target_agenda') ?>
            </label>
        </div>

        <div class="form-group moveToAgendaItem">
            <label class="col-md-3 control-label"><?= Yii::t('admin', 'motion_move_agenda_item') ?>:</label>
            <div class="col-md-9">
                <?php
                $options    = ['id' => 'agendaItemId' . $consultation->id];
                $selections = [];
                foreach (ConsultationAgendaItem::getSortedFromConsultation($consultation) as $item) {
                    $selections[$item->id] = $item->title;
                }

                echo HTMLTools::fueluxSelectbox('agendaItem[' . $consultation->id . ']', $selections, $motion->agendaItemId, $options, true);
                ?>
            </div>
        </div>

    </div>
    <div class="saveholder">
        <button type="submit" name="move" class="btn btn-primary save"><?= Yii::t('admin', 'save') ?></button>
    </div>
<?php
echo Html::endForm();
