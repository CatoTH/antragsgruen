<?php

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\Motion;
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

$this->title = 'Antrag bearbeiten: ' . $motion->getTitleWithPrefix();
$layout->addBreadcrumb('Administration', UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb('Anträge', UrlHelper::createUrl('admin/motion/listall'));
$layout->addBreadcrumb('Antrag');

$layout->addJS('js/backend.js');
$layout->addCSS('css/backend.css');
$layout->loadDatepicker();
$layout->loadCKEditor();

$html = '<ul class="sidebarActions">';
$html .= '<li><a href="' . Html::encode(UrlHelper::createMotionUrl($motion)) . '" class="view">';
$html .= '<span class="glyphicon glyphicon-file"></span> Antrag anzeigen' . '</a></li>';

$cloneUrl = UrlHelper::createUrl(['motion/create', 'adoptInitiators' => $motion->id]);
$html .= '<li><a href="' . Html::encode($cloneUrl) . '" class="clone">';
$html .= '<span class="glyphicon glyphicon-duplicate"></span> Neuer Antrag auf dieser Basis</a></li>';

$html .= '<li>' . Html::beginForm('', 'post', ['class' => 'motionDeleteForm']);
$html .= '<input type="hidden" name="delete" value="1">';
$html .= '<button type="submit" class="link"><span class="glyphicon glyphicon-trash"></span> '
    . 'Antrag löschen' . '</button>';
$html .= Html::endForm() . '</li>';

$html .= '</ul>';
$layout->menusHtml[] = $html;


echo '<h1>' . Html::encode($motion->getTitleWithPrefix()) . '</h1>';

echo $controller->showErrors();


if ($motion->status == Motion::STATUS_SUBMITTED_UNSCREENED) {
    echo Html::beginForm('', 'post', ['class' => 'content', 'id' => 'motionScreenForm']);
    $newRev = $motion->titlePrefix;
    if ($newRev == '') {
        $newRev = $motion->getConsultation()->getNextMotionPrefix($motion->motionTypeId);
    }

    echo '<input type="hidden" name="titlePrefix" value="' . Html::encode($newRev) . '">';

    echo '<div style="text-align: center;"><button type="submit" class="btn btn-primary" name="screen">';
    echo Html::encode('Freischalten als ' . $newRev);
    echo '</button></div>';

    echo Html::endForm();

    echo "<br>";
}


echo Html::beginForm('', 'post', ['id' => 'motionUpdateForm', 'enctype' => 'multipart/form-data']);

echo '<div class="content form-horizontal">';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="parentMotion">';
echo 'Überarbeitete Fassung von';
echo ':</label><div class="col-md-9">';
echo '<select class="form-control" name="motion[parentMotionId]" id="parentMotion"><option>-</option>';
foreach ($consultation->motions as $mot) {
    if ($mot->id != $motion->id) {
        echo '<option value="' . $mot->id . '"';
        if ($motion->parentMotionId == $mot->id) {
            echo ' selected';
        }
        echo '>' . Html::encode($mot->getTitleWithPrefix()) . '</option>';
    }
}
echo '</select></div></div>';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="motionStatus">';
echo 'Status';
echo ':</label><div class="col-md-4">';
$options = ['class' => 'form-control', 'id' => 'motionStatus'];
echo Html::dropDownList('motion[status]', $motion->status, Motion::getStati(), $options);
echo '</div><div class="col-md-5">';
$options = ['class' => 'form-control', 'id' => 'motionStatusString', 'placeholder' => '...'];
echo Html::textInput('motion[statusString]', $motion->statusString, $options);
echo '</div></div>';

if (count($consultation->agendaItems) > 0) {
    echo '<div class="form-group">';
    echo '<label class="col-md-3 control-label" for="motionStatus">';
    echo 'Tagesordnungspunkt';
    echo ':</label><div class="col-md-9">';
    $options    = ['class' => 'form-control', 'id' => 'agendaItemId'];
    $selections = [];
    foreach (ConsultationAgendaItem::getSortedFromConsultation($consultation) as $item) {
        $selections[$item->id] = $item->title;
    }

    echo Html::dropDownList('motion[agendaItemId]', $motion->agendaItemId, $selections, $options);
    echo '</div></div>';
}


echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="motionTitle">';
echo 'Titel';
echo ':</label><div class="col-md-9">';
$options = ['class' => 'form-control', 'id' => 'motionTitle', 'placeholder' => 'Titel'];
echo Html::textInput('motion[title]', $motion->title, $options);
echo '</div></div>';


echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="motionTitlePrefix">';
echo 'Antragskürzel';
echo ':</label><div class="col-md-4">';
$options = ['class' => 'form-control', 'id' => 'motionTitlePrefix', 'placeholder' => 'z.B. "A1", "A1neu", "S1"'];
echo Html::textInput('motion[titlePrefix]', $motion->titlePrefix, $options);
echo '<small>Muss eindeutig sein.</small>';
echo '</div></div>';


$locale = Tools::getCurrentDateLocale();

$date = Tools::dateSql2bootstraptime($motion->dateCreation);
echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="motionDateCreation">';
echo 'Angelegt am';
echo ':</label><div class="col-md-4"><div class="input-group date" id="motionDateCreationHolder">';
echo '<input type="text" class="form-control" name="motion[dateCreation]" id="motionDateCreation"
                value="' . Html::encode($date) . '" data-locale="' . Html::encode($locale) . '">
            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>';
echo '</div></div></div>';

$date = Tools::dateSql2bootstraptime($motion->dateResolution);
echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="motionDateResolution">';
echo 'Beschlossen am';
echo ':</label><div class="col-md-4"><div class="input-group date" id="motionDateResolutionHolder">';
echo '<input type="text" class="form-control" name="motion[dateResolution]" id="motionDateResolution"
                value="' . Html::encode($date) . '" data-locale="' . Html::encode($locale) . '">
            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>';
echo '</div></div></div>';


if (count($consultation->tags) > 0) {
    echo '<div class="form-group">';
    echo '<div class="col-md-3 control-label label">';
    echo 'Themen';
    echo ':</div><div class="col-md-9 tagList">';
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
echo '<label class="col-md-3 control-label" for="motionNoteInternal">';
echo 'Interne Notiz';
echo ':</label><div class="col-md-9">';
$options = ['class' => 'form-control', 'id' => 'motionNoteInternal'];
echo Html::textarea('motion[noteInternal]', $motion->noteInternal, $options);
echo '</div></div>';


echo '</div>';


if (!$motion->textFixed) {
    echo '<h2 class="green">' . 'Text bearbeiten' . '</h2>
<div class="content" id="motionTextEditCaller">
    <strong>Vorsicht:</strong> Wenn es bereits Änderungsanträge und Kommentare zu einem Antrag gibt,
    ist es gefährlich, den Text zu ändern, da sich die Absatzzuordnung ändern und sich zusätzliche Änderungen
    einschleichen könnten.
    <br><br>
    <button type="button" class="btn btn-default">Bearbeiten</button>
</div>
<div class="content hidden" id="motionTextEditHolder">';

    foreach ($form->sections as $section) {
        if ($section->getSettings()->type == \app\models\sectionTypes\ISectionType::TYPE_TITLE) {
            continue;
        }
        echo $section->getSectionType()->getMotionFormField();
    }

    echo '</div>';
}

$initiatorClass = $form->motionType->getMotionSupportTypeClass();
$initiatorClass->setAdminMode(true);
echo $initiatorClass->getMotionForm($form->motionType, $form, $controller);


echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">Speichern</button>
</div>';

echo Html::endForm();

$layout->addOnLoadJS('jQuery.AntragsgruenAdmin.motionEditInit();');
