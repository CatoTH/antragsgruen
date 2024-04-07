<?php

use app\components\{HTMLTools, UrlHelper};
use app\views\pdfLayouts\IPDFLayout;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('admin', 'motion_type_create_head');
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_types'));
$layout->addCSS('css/backend.css');

echo '<h1>' . $this->title . '</h1>';
echo Html::beginForm('', 'post', [
    'class'                    => 'motionTypeCreateForm content form-horizontal',
    'data-antragsgruen-widget' => 'backend/MotionTypeCreate',
]);

?>
<div class="stdTwoCols">
    <label class="leftColumn">
        <?= Yii::t('admin', 'motion_type_templ') ?>:
    </label>
    <div class="rightColumn typePresetList">
        <?php
        foreach ($controller->consultation->motionTypes as $motionType) {
            ?>
            <label class="typePreset">
                <input type="radio" name="type[preset]" value="<?= $motionType->id ?>"
                       class="preset<?= $motionType->id ?>" required>
                <span><?= Html::encode($motionType->titleSingular) ?></span>
            </label>
            <div class="typePresetInfo"></div>
            <?php
        }
        ?>

        <label class="typePreset"
               data-label-single="<?= Yii::t('structure', 'preset_motion_singular') ?>"
               data-label-plural="<?= Yii::t('structure', 'preset_motion_plural') ?>"
               data-label-prefix=""
               data-label-cta="<?= Yii::t('structure', 'preset_motion_call') ?>">
            <input type="radio" name="type[preset]" value="motion" class="presetMotion">
            <span><?= Yii::t('admin', 'motion_type_templ_motion') ?></span>
        </label>
        <div class="typePresetInfo"><?= Yii::t('admin', 'motion_type_templ_motionh') ?></div>

        <label class="typePreset"
               data-label-single="<?= Yii::t('structure', 'preset_app_singular') ?>"
               data-label-plural="<?= Yii::t('structure', 'preset_app_plural') ?>"
               data-label-prefix=""
               data-label-cta="<?= Yii::t('structure', 'preset_app_call') ?>">
            <input type="radio" name="type[preset]" value="application" class="presetApplication">
            <span><?= Yii::t('admin', 'motion_type_templ_appl') ?></span>
        </label>
        <div class="typePresetInfo"><?= Yii::t('admin', 'motion_type_templ_applh') ?></div>

        <label class="typePreset"
               data-label-single="<?= Yii::t('structure', 'preset_app_singular') ?>"
               data-label-plural="<?= Yii::t('structure', 'preset_app_plural') ?>"
               data-label-prefix=""
               data-label-cta="<?= Yii::t('structure', 'preset_app_call') ?>">
            <input type="radio" name="type[preset]" value="pdfapplication" class="presetPdfApplication">
            <span><?= Yii::t('admin', 'motion_type_templ_pdfappl') ?></span>
        </label>
        <div class="typePresetInfo"><?= Yii::t('admin', 'motion_type_templ_pdfapplh') ?></div>

        <label class="typePreset"
               data-label-single="<?= Yii::t('structure', 'preset_progress_singular') ?>"
               data-label-plural="<?= Yii::t('structure', 'preset_progress_plural') ?>"
               data-label-prefix=""
               data-label-cta="<?= Yii::t('structure', 'preset_progress_call') ?>">
            <input type="radio" name="type[preset]" value="progress" class="presetProgress">
            <span><?= Yii::t('admin', 'motion_type_templ_progress') ?></span>
        </label>
        <div class="typePresetInfo"><?= Yii::t('admin', 'motion_type_templ_progressh') ?></div>

        <label class="typePreset"
               data-label-single="<?= Yii::t('structure', 'preset_statutes_singular') ?>"
               data-label-plural="<?= Yii::t('structure', 'preset_statutes_plural') ?>"
               data-label-prefix="S"
               data-label-cta="<?= Yii::t('structure', 'preset_statutes_call') ?>">
            <input type="radio" name="type[preset]" value="statute" class="presetStatute">
            <span><?= Yii::t('admin', 'motion_type_templ_statute') ?></span>
        </label>
        <div class="typePresetInfo"><?= Yii::t('admin', 'motion_type_templ_statuteh') ?></div>

        <label class="typePreset">
            <input type="radio" name="type[preset]" value="none" class="presetNone">
            <span><?= Yii::t('admin', 'motion_type_templ_none') ?></span>
        </label>
        <div class="typePresetInfo"></div>
    </div>
</div>


<div class="stdTwoCols">
    <label class="leftColumn" for="typeTitleSingular">
        <?= Yii::t('admin', 'motion_type_singular') ?>:
    </label>
    <div class="rightColumn"><?php
        $options = [
            'class'    => 'form-control',
            'id'       => 'typeTitleSingular',
            'required' => 'required',
        ];
        echo Html::textInput('type[titleSingular]', '', $options);
        ?>
    </div>
</div>

<div class="stdTwoCols">
    <label class="leftColumn" for="typeTitlePlural">
        <?= Yii::t('admin', 'motion_type_plural') ?>:
    </label>
    <div class="rightColumn"><?php
        $options = [
            'class'    => 'form-control',
            'id'       => 'typeTitlePlural',
            'required' => 'required',
        ];
        echo Html::textInput('type[titlePlural]', '', $options);
        ?>
    </div>
</div>

<div class="stdTwoCols">
    <label class="leftColumn" for="typeCreateTitle">
        <?= Yii::t('admin', 'motion_type_create_title') ?>:
    </label>
    <div class="rightColumn"><?php
        $options = [
            'class'    => 'form-control',
            'id'       => 'typeCreateTitle',
            'required' => 'required',
        ];
        echo HTMLTools::smallTextarea('type[createTitle]', $options, '');
        ?>
    </div>
</div>

<div class="stdTwoCols">
    <label class="leftColumn" for="typeMotionPrefix">
        <?= Yii::t('admin', 'motion_type_title_prefix') ?>:
    </label>
    <div class="rightColumn">
        <?php
        $options = ['class' => 'form-control', 'id' => 'typeMotionPrefix', 'placeholder' => 'A'];
        echo Html::textInput('type[motionPrefix]', '', $options);
        ?>
    </div>
</div>

<div class="saveholder">
    <button type="submit" name="create" class="btn btn-primary">
        <?= Yii::t('admin', 'motion_type_create_submit') ?>
    </button>
</div>

<?= Html::endForm() ?>
