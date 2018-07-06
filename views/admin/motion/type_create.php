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
$layout->loadFuelux();
$layout->addCSS('css/backend.css');

echo '<h1>' . $this->title . '</h1>';
echo Html::beginForm('', 'post', [
    'class'                    => 'motionTypeCreateForm content form-horizontal fuelux',
    'data-antragsgruen-widget' => 'backend/MotionTypeCreate',
]);

?>
<div class="form-group">
    <label class="col-md-3 control-label">
        <?= \Yii::t('admin', 'motion_type_templ') ?>:
    </label>
    <div class="col-md-9 typePresetList">
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
               data-label-single="<?= \Yii::t('admin', 'motion_type_templ_motsingle') ?>"
               data-label-plural="<?= \Yii::t('admin', 'motion_type_templ_motplural') ?>"
               data-label-cta="<?= \Yii::t('admin', 'motion_type_templ_motcta') ?>">
            <input type="radio" name="type[preset]" value="motion" class="presetMotion">
            <span><?= \Yii::t('admin', 'motion_type_templ_motion') ?></span>
        </label>
        <div class="typePresetInfo"><?= \Yii::t('admin', 'motion_type_templ_motionh') ?></div>

        <label class="typePreset"
               data-label-single="<?= \Yii::t('admin', 'motion_type_templ_appsingle') ?>"
               data-label-plural="<?= \Yii::t('admin', 'motion_type_templ_appplural') ?>"
               data-label-cta="<?= \Yii::t('admin', 'motion_type_templ_appcta') ?>">
            <input type="radio" name="type[preset]" value="application" class="presetApplication">
            <span><?= \Yii::t('admin', 'motion_type_templ_appl') ?></span>
        </label>
        <div class="typePresetInfo"><?= \Yii::t('admin', 'motion_type_templ_applh') ?></div>

        <label class="typePreset"
               data-label-single="<?= \Yii::t('admin', 'motion_type_templ_appsingle') ?>"
               data-label-plural="<?= \Yii::t('admin', 'motion_type_templ_appplural') ?>"
               data-label-cta="<?= \Yii::t('admin', 'motion_type_templ_appcta') ?>">
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


<div class="form-group">
    <label class="col-md-3 control-label" for="typeTitleSingular">
        <?= \Yii::t('admin', 'motion_type_singular') ?>
    </label>
    <div class="col-md-9"><?php
        $options = [
            'class'    => 'form-control',
            'id'       => 'typeTitleSingular',
            'required' => 'required',
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
            'class'    => 'form-control',
            'id'       => 'typeTitlePlural',
            'required' => 'required',
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
            'class'    => 'form-control',
            'id'       => 'typeCreateTitle',
            'required' => 'required',
        ];
        echo HTMLTools::smallTextarea('type[createTitle]', $options, '');
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-md-3 control-label" for="pdfLayout">
        <?= \Yii::t('admin', 'motion_type_pdf_layout') ?>
    </label>
    <div class="col-md-9 thumbnailedLayoutSelector">
        <?php
        $hasTex = isset($motionType->getAvailablePDFTemplates()[1]);
        foreach ($motionType->getAvailablePDFTemplates() as $lId => $layout) {
            if ($hasTex) {
                $checked = ($lId === 1);
            } else {
                $checked = ($lId === 'php0');
            }
            echo '<label class="layout">';
            echo Html::radio('type[pdfLayout]', $checked, ['value' => $lId, 'required' => 'required']);
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

<div class="saveholder">
    <button type="submit" name="create" class="btn btn-primary">
        <?= \Yii::t('admin', 'motion_type_create_submit') ?>
    </button>
</div>

<?= Html::endForm() ?>
