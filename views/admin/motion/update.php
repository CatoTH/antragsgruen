<?php

use app\components\HTMLTools;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var Consultation $consultation
 * @var Motion $motion
 * @var \app\models\forms\MotionEditForm $form
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = \Yii::t('admin', 'motion_edit_title') . ': ' . $motion->getTitleWithPrefix();
$layout->addBreadcrumb(\Yii::t('admin', 'bread_list'), UrlHelper::createUrl('admin/motion/listall'));
$layout->addBreadcrumb(\Yii::t('admin', 'bread_motion'));

$layout->addCSS('css/backend.css');
$layout->loadSortable();
$layout->loadDatepicker();
$layout->loadCKEditor();
$layout->loadFuelux();
$layout->addAMDModule('backend/MotionEdit');

$html = '<ul class="sidebarActions">';
$html .= '<li><a href="' . Html::encode(UrlHelper::createMotionUrl($motion)) . '" class="view">';
$html .= '<span class="glyphicon glyphicon-file"></span> ' . \Yii::t('admin', 'motion_show') . '</a></li>';

$cloneUrl = UrlHelper::createUrl(['motion/create', 'cloneFrom' => $motion->id]);
$html .= '<li><a href="' . Html::encode($cloneUrl) . '" class="clone">';
$html .= '<span class="glyphicon glyphicon-duplicate"></span> ' .
    \Yii::t('admin', 'motion_new_base_on_this') . '</a></li>';

$html .= '<li>' . Html::beginForm('', 'post', ['class' => 'motionDeleteForm']);
$html .= '<input type="hidden" name="delete" value="1">';
$html .= '<button type="submit" class="link"><span class="glyphicon glyphicon-trash"></span> '
    . \Yii::t('admin', 'motion_del') . '</button>';
$html .= Html::endForm() . '</li>';

$html .= '</ul>';
$layout->menusHtml[] = $html;


echo '<h1>' . $motion->getEncodedTitleWithPrefix() . '</h1>';

echo $controller->showErrors();


if ($motion->isInScreeningProcess()) {
    echo Html::beginForm('', 'post', ['class' => 'content', 'id' => 'motionScreenForm']);
    $newRev = $motion->titlePrefix;
    if ($newRev == '') {
        $newRev = $motion->getMyConsultation()->getNextMotionPrefix($motion->motionTypeId);
    }

    echo '<input type="hidden" name="titlePrefix" value="' . Html::encode($newRev) . '">';

    echo '<div style="text-align: center;"><button type="submit" class="btn btn-primary" name="screen">';
    echo Html::encode(str_replace('%PREFIX%', $newRev, \Yii::t('admin', 'motion_screen_as_x')));
    echo '</button></div>';

    echo Html::endForm();

    echo "<br>";
}


echo Html::beginForm('', 'post', ['id' => 'motionUpdateForm', 'enctype' => 'multipart/form-data']);

echo '<div class="content form-horizontal fuelux">';

?>

<div class="form-group">
    <label class="col-md-3 control-label" for="motionType"><?= \Yii::t('admin', 'motion_type') ?></label>
    <div class="col-md-9"><?php
    $options = [];
    foreach ($motion->motionType->getCompatibleMotionTypes() as $motionType) {
        $options[$motionType->id] = $motionType->titleSingular;
    }
    $attrs = ['id' => 'motionType', 'class' => 'form-control'];
    echo HTMLTools::fueluxSelectbox('motion[motionType]', $options, $motion->motionTypeId, $attrs);
    ?></div>
</div>

<div class="form-group">
    <label class="col-md-3 control-label" for="parentMotion"><?= \Yii::t('admin', 'motion_replaces') ?></label>
    <div class="col-md-9"><?php
    $options = ['-'];
    foreach ($consultation->motions as $otherMotion) {
        $options[$otherMotion->id] = $otherMotion->getTitleWithPrefix();
    }
    $attrs = ['id' => 'parentMotion', 'class' => 'form-control'];
    echo HTMLTools::fueluxSelectbox('motion[parentMotionId]', $options, $motion->parentMotionId, $attrs);
    ?></div>
</div>

<div class="form-group">
    <label class="col-md-3 control-label" for="motionStatus"><?= \Yii::t('admin', 'motion_status') ?>:</label>
    <div class="col-md-5"><?php
    $options = ['class' => 'form-control', 'id' => 'motionStatus'];
    echo HTMLTools::fueluxSelectbox('motion[status]', Motion::getStati(), $motion->status, $options);
    echo '</div><div class="col-md-4">';
    $options = ['class' => 'form-control', 'id' => 'motionStatusString', 'placeholder' => '...'];
    echo Html::textInput('motion[statusString]', $motion->statusString, $options);
    ?></div>
</div>

<?php
if (count($consultation->agendaItems) > 0) {
    echo '<div class="form-group">';
    echo '<label class="col-md-3 control-label" for="agendaItemId">';
    echo \Yii::t('admin', 'motion_agenda_item');
    echo ':</label><div class="col-md-9">';
    $options    = ['class' => 'form-control', 'id' => 'agendaItemId'];
    $selections = [];
    foreach (ConsultationAgendaItem::getSortedFromConsultation($consultation) as $item) {
        $selections[$item->id] = $item->title;
    }

    echo Html::dropDownList('motion[agendaItemId]', $motion->agendaItemId, $selections, $options);
    echo '</div></div>';
}
?>

<div class="form-group">
    <label class="col-md-3 control-label" for="motionTitle"><?=  \Yii::t('admin', 'motion_title') ?>:</label>
    <div class="col-md-9"><?php
    $options = ['class' => 'form-control', 'id' => 'motionTitle', 'placeholder' => \Yii::t('admin', 'motion_title')];
    echo Html::textInput('motion[title]', $motion->title, $options);
    ?></div>
</div>

<div class="form-group">
    <label class="col-md-3 control-label" for="motionTitlePrefix"><?= \Yii::t('admin', 'motion_prefix') ?>:</label>
    <div class="col-md-4"><?php
    echo Html::textInput('motion[titlePrefix]', $motion->titlePrefix, [
        'class'       => 'form-control',
        'id'          => 'motionTitlePrefix',
        'placeholder' => \Yii::t('admin', 'motion_prefix_hint')
    ]);
    ?>
        <small><?= \Yii::t('admin', 'motion_prefix_unique') ?></small>
    </div>
</div>

<?php
$locale = Tools::getCurrentDateLocale();
$date = Tools::dateSql2bootstraptime($motion->dateCreation);
echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="motionDateCreation">';
echo \Yii::t('admin', 'motion_date_created');
echo ':</label><div class="col-md-4"><div class="input-group date" id="motionDateCreationHolder">';
echo '<input type="text" class="form-control" name="motion[dateCreation]" id="motionDateCreation"
                value="' . Html::encode($date) . '" data-locale="' . Html::encode($locale) . '">
            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>';
echo '</div></div></div>';

$date = Tools::dateSql2bootstraptime($motion->dateResolution);
echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="motionDateResolution">';
echo \Yii::t('admin', 'motion_date_resolution');
echo ':</label><div class="col-md-4"><div class="input-group date" id="motionDateResolutionHolder">';
echo '<input type="text" class="form-control" name="motion[dateResolution]" id="motionDateResolution"
                value="' . Html::encode($date) . '" data-locale="' . Html::encode($locale) . '">
            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>';
echo '</div></div></div>';


if (count($consultation->tags) > 0) {
    echo '<div class="form-group">';
    echo '<label class="col-md-3 control-label">';
    echo \Yii::t('admin', 'motion_topics');
    echo ':</label><div class="col-md-9 tagList">';
    foreach ($consultation->tags as $tag) {
        echo '<label><input type="checkbox" name="tags[]" value="' . $tag->id . '"';
        foreach ($motion->tags as $mtag) {
            if ($mtag->id == $tag->id) {
                echo ' checked';
            }
        }
        echo '> ' . Html::encode($tag->title) . '</label>';
    }
    echo '</div></div>';
}

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="nonAmendable">';
echo \Yii::t('admin', 'motion_non_amendable_title');
echo ':</label><div class="col-md-9 nonAmendable">';
echo '<label><input type="checkbox" name="motion[nonAmendable]" value="1"';
if ($motion->nonAmendable) {
    echo ' checked';
}
echo ' id="nonAmendable"> ' . \Yii::t('admin', 'motion_non_amendable') . '</label>';
echo '</div></div>';


echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="motionNoteInternal">';
echo \Yii::t('admin', 'motion_note_internal');
echo ':</label><div class="col-md-9">';
$options = ['class' => 'form-control', 'id' => 'motionNoteInternal'];
echo Html::textarea('motion[noteInternal]', $motion->noteInternal, $options);
echo '</div></div>';


echo '</div>';


$needsCollissionCheck = (!$motion->textFixed && count($motion->getAmendmentsRelevantForCollissionDetection()) > 0);
if (!$motion->textFixed) {
    echo '<h2 class="green">' . \Yii::t('admin', 'motion_edit_text') . '</h2>
<div class="content" id="motionTextEditCaller">' .
        \Yii::t('admin', 'motion_edit_text_warn') . '
    <br><br>
    <button type="button" class="btn btn-default">' . \Yii::t('admin', 'motion_edit_btn') . '</button>
</div>
<div class="content hidden" id="motionTextEditHolder">';

    if ($needsCollissionCheck) {
        echo '<div class="alert alert-danger" role="alert">
            <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
            <span class="sr-only">' . \Yii::t('admin', 'motion_amrew_warning') . ':</span> ' .
            \Yii::t('admin', 'motion_amrew_intro') .
            '</div>';
    }

    foreach ($form->sections as $section) {
        if ($section->getSettings()->type == \app\models\sectionTypes\ISectionType::TYPE_TITLE) {
            continue;
        }
        echo $section->getSectionType()->getMotionFormField();
    }

    $url = UrlHelper::createUrl(['admin/motion/get-amendment-rewrite-collissions', 'motionId' => $motion->id]);
    echo '<section class="amendmentCollissionsHolder"></section>';
    if ($needsCollissionCheck) {
        echo '<div class="checkButtonRow">';
        echo '<button class="checkAmendmentCollissions btn btn-default" data-url="' . Html::encode($url) . '">' .
            \Yii::t('admin', 'motion_amrew_btn1') . '</button>';
        echo '</div>';
    }
    echo '</div>';
}

$initiatorClass = $form->motionType->getMotionSupportTypeClass();
$initiatorClass->setAdminMode(true);
echo $initiatorClass->getMotionForm($form->motionType, $form, $controller);

echo $this->render('_update_supporter', [
    'supporters'  => $motion->getSupporters(),
    'newTemplate' => new MotionSupporter()
]);

echo '<div class="saveholder">';
if ($needsCollissionCheck) {
    $url = UrlHelper::createUrl(['admin/motion/get-amendment-rewrite-collissions', 'motionId' => $motion->id]);
    echo '<button class="checkAmendmentCollissions btn btn-default" data-url="' . Html::encode($url) . '">' .
        \Yii::t('admin', 'motion_amrew_btn2') . '</button>';
}
echo '<button type="submit" name="save" class="btn btn-primary save">' . \Yii::t('admin', 'save') . '</button>
</div>';

echo Html::endForm();
