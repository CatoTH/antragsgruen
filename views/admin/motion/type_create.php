<?php

use app\components\HTMLTools;
use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = \Yii::t('admin', 'motion_type_create_head');
$layout->addBreadcrumb(\Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(\Yii::t('admin', 'bread_types'));

$layout->addCSS('css/backend.css');

echo '<h1>' . $this->title . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'motionTypeCreateForm content form-horizontal']);

?>
<div class="form-group">
    <label class="col-md-3 control-label" for="typeTitleSingular">
        <?= \Yii::t('admin', 'motion_type_singular') ?>
    </label>
    <div class="col-md-9"><?php
        $options = [
            'class'       => 'form-control',
            'id'          => 'typeTitleSingular',
            'placeholder' => \Yii::t('admin', 'motion_type_singular_pl'),
            'required'    => 'required',
        ];
        echo Html::textInput('type[titleSingular]', '', $options);
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-md-3 control-label" for="typeTitlePlural">
        <?= \Yii::t('admin', 'motion_type_plural') ?>
    </label>
    <div class="col-md-9"><?php
        $options = [
            'class'       => 'form-control',
            'id'          => 'typeTitlePlural',
            'placeholder' => \Yii::t('admin', 'motion_type_plural_pl'),
            'required'    => 'required',
        ];
        echo Html::textInput('type[titlePlural]', '', $options);
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-md-3 control-label" for="typeCreateTitle">
        <?= \Yii::t('admin', 'motion_type_create_title') ?>
    </label>
    <div class="col-md-9"><?php
        $options = [
            'class'       => 'form-control',
            'id'          => 'typeCreateTitle',
            'placeholder' => \Yii::t('admin', 'motion_type_create_placeh'),
            'required'    => 'required',
        ];
        echo HTMLTools::smallTextarea('type[createTitle]', $options, '');
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-md-3 control-label" for="pdfLayout">
        <?= \Yii::t('admin', 'motion_type_pdf_layout') ?>
    </label>
    <div class="col-md-9"><?php
        echo Html::dropDownList(
            'type[pdfLayout]',
            1, // BDK-Layout
            \app\views\pdfLayouts\IPDFLayout::getClasses(),
            ['id' => 'pdfLayout', 'class' => 'form-control']
        );
        ?></div>
</div>

<div class="form-group">
    <label class="col-md-3 control-label" for="typeMotionPrefix">
        <?= \Yii::t('admin', 'motion_type_title_prefix') ?>
    </label>
    <div class="col-md-2">
        <?php
        $options = ['class' => 'form-control', 'id' => 'typeMotionPrefix', 'placeholder' => 'A'];
        echo Html::textInput('type[motionPrefix]', '', $options);
        ?>
    </div>
</div>


<div class="form-group">
    <label class="col-md-3 control-label">
        <?= \Yii::t('admin', 'motion_type_templ') ?>:
    </label>
    <div class="col-md-9">
        <?php
        foreach ($controller->consultation->motionTypes as $motionType) {
            ?>
            <label class="typePreset">
                <input type="radio" name="type[preset]" value="<?= $motionType->id ?>"
                       class="preset<?= $motionType->id ?>">
                <span><?= Html::encode($motionType->titleSingular) ?></span>
            </label>
            <div class="typePresetInfo"></div>
            <?php
        }
        ?>

        <label class="typePreset">
            <input type="radio" name="type[preset]" value="motion" class="presetMotion">
            <span><?= \Yii::t('admin', 'motion_type_templ_motion') ?></span>
        </label>
        <div class="typePresetInfo"><?= \Yii::t('admin', 'motion_type_templ_motionh') ?></div>

        <label class="typePreset">
            <input type="radio" name="type[preset]" value="application" class="presetApplication">
            <span><?= \Yii::t('admin', 'motion_type_templ_appl') ?></span>
        </label>
        <div class="typePresetInfo"><?= \Yii::t('admin', 'motion_type_templ_applh') ?></div>

        <label class="typePreset">
            <input type="radio" name="type[preset]" value="pdfapplication" class="presetPdfApplication">
            <span><?= \Yii::t('admin', 'motion_type_templ_pdfappl') ?></span>
        </label>
        <div class="typePresetInfo"><?= \Yii::t('admin', 'motion_type_templ_pdfapplh') ?></div>

        <label class="typePreset">
            <input type="radio" name="type[preset]" value="none" class="presetNone">
            <span><?= \Yii::t('admin', 'motion_type_templ_none') ?></span>
        </label>
        <div class="typePresetInfo"></div>


    </div>
</div>


<div class="saveholder">
    <button type="submit" name="create" class="btn btn-primary">
        <?= \Yii::t('admin', 'motion_type_create_submit') ?>
    </button>
</div>

<?= Html::endForm() ?>
