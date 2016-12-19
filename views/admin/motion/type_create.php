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


echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typeTitleSingular">';
echo \Yii::t('admin', 'motion_type_singular');
echo '</label><div class="col-md-9">';
$options = [
    'class'       => 'form-control',
    'id'          => 'typeTitleSingular',
    'placeholder' => \Yii::t('admin', 'motion_type_singular_pl'),
    'required'    => 'required',
];
echo Html::textInput('type[titleSingular]', '', $options);
echo '</div></div>';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typeTitlePlural">';
echo \Yii::t('admin', 'motion_type_plural');
echo '</label><div class="col-md-9">';
$options = [
    'class'       => 'form-control',
    'id'          => 'typeTitlePlural',
    'placeholder' => \Yii::t('admin', 'motion_type_plural_pl'),
    'required'    => 'required',
];
echo Html::textInput('type[titlePlural]', '', $options);
echo '</div></div>';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typeCreateTitle">';
echo \Yii::t('admin', 'motion_type_create_title');
echo '</label><div class="col-md-9">';

$options = [
    'class'       => 'form-control',
    'id'          => 'typeCreateTitle',
    'placeholder' => \Yii::t('admin', 'motion_type_create_placeh'),
    'required'    => 'required',
];
echo HTMLTools::smallTextarea('type[createTitle]', $options, '');
echo '</div></div>';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="pdfLayout">';
echo \Yii::t('admin', 'motion_type_pdf_layout');
echo '</label><div class="col-md-9">';
echo Html::dropDownList(
    'type[pdfLayout]',
    1, // BDK-Layout
    \app\views\pdfLayouts\IPDFLayout::getClasses(),
    ['id' => 'pdfLayout', 'class' => 'form-control']
);
echo '</div></div>';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="typeMotionPrefix">';
echo \Yii::t('admin', 'motion_type_title_prefix');
echo '</label><div class="col-md-2">';
$options = ['class' => 'form-control', 'id' => 'typeMotionPrefix', 'placeholder' => 'A'];
echo Html::textInput('type[motionPrefix]', '', $options);
echo '</div></div>';


echo '<div class="form-group">';
echo '<label class="col-md-3 control-label">';
echo \Yii::t('admin', 'motion_type_templ') . ':';
echo '</label><div class="col-md-9">';


foreach ($controller->consultation->motionTypes as $motionType) {
    echo '<label class="typePreset">';
    echo '<input type="radio" name="type[preset]" value="' . $motionType->id . '" class="preset' . $motionType->id . '">';
    echo '<span>' . Html::encode($motionType->titleSingular) . '</span>';
    echo '</label>';
    echo '<div class="typePresetInfo"></div>';
}

echo '<label class="typePreset">';
echo '<input type="radio" name="type[preset]" value="motion" class="presetMotion">';
echo '<span>' . \Yii::t('admin', 'motion_type_templ_motion') . '</span>';
echo '</label>';
echo '<div class="typePresetInfo">' . \Yii::t('admin', 'motion_type_templ_motionh') . '</div>';

echo '<label class="typePreset">';
echo '<input type="radio" name="type[preset]" value="application" class="presetApplication">';
echo '<span>' . \Yii::t('admin', 'motion_type_templ_appl') . '</span>';
echo '</label>';
echo '<div class="typePresetInfo">' . \Yii::t('admin', 'motion_type_templ_applh') . '</div>';

echo '<label class="typePreset">';
echo '<input type="radio" name="type[preset]" value="none" class="presetNone">';
echo '<span>' . \Yii::t('admin', 'motion_type_templ_none') . '</span>';
echo '</label>';
echo '<div class="typePresetInfo"></div>';


echo '</div></div>';


echo '<div class="saveholder"><button type="submit" name="create" class="btn btn-primary">' .
    \Yii::t('admin', 'motion_type_create_submit') . '</button></div>';


echo Html::endForm();
