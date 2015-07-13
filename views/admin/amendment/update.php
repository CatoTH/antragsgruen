<?php

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var Consultation $consultation
 * @var Amendment $amendment
 * @var \app\models\forms\AmendmentEditForm $form
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = 'Änderungsantrag bearbeiten: ' . $amendment->getTitle();
$layout->addBreadcrumb('Administration', UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb('Anträge', UrlHelper::createUrl('admin/motion/listall'));
$layout->addBreadcrumb('Änderungsantrag');

$layout->addJS('/js/backend.js');
$layout->addCSS('/css/backend.css');
$layout->loadDatepicker();
$layout->loadCKEditor();


$html = '<ul class="sidebarActions">';
$html .= '<li><a href="' . Html::encode(UrlHelper::createAmendmentUrl($amendment)) . '" class="view">';
$html .= '<span class="glyphicon glyphicon-file"></span> Änderungsantrag anzeigen' . '</a></li>';

$params = ['amendment/create', 'motionId' => $amendment->motionId, 'adoptInitiators' => $amendment->id];
$cloneUrl = Html::encode(UrlHelper::createUrl($params));
$html .= '<li><a href="' . $cloneUrl . '" class="clone">';
$html .= '<span class="glyphicon glyphicon-duplicate"></span> Neuer Ä.-Antrag auf dieser Basis</a></li>';

$html .= '<li>' . Html::beginForm('', 'post', ['class' => 'amendmentDeleteForm']);
$html .= '<input type="hidden" name="delete" value="1">';
$html .= '<button type="submit" class="link"><span class="glyphicon glyphicon-trash"></span> '
    . 'Änderungsantrag löschen' . '</button>';
$html .= Html::endForm() . '</li>';

$html .= '</ul>';
$layout->menusHtml[] = $html;



echo '<h1>' . Html::encode($amendment->getTitle()) . '</h1>';

echo $controller->showErrors();




if ($amendment->status == Amendment::STATUS_SUBMITTED_UNSCREENED) {
    echo Html::beginForm('', 'post', ['class' => 'content', 'id' => 'amendmentScreenForm']);
    $newRev = $amendment->titlePrefix;
    if ($newRev == '') {
        $newRev = $amendment->motion->consultation->getNextAmendmentPrefix($amendment->motionId);
    }

    echo '<input type="hidden" name="titlePrefix" value="' . Html::encode($newRev) . '">';

    echo '<div style="text-align: center;"><button type="submit" class="btn btn-primary" name="screen">';
    echo Html::encode('Freischalten als ' . $newRev);
    echo '</button></div>';

    echo Html::endForm();

    echo "<br>";
}


echo Html::beginForm('', 'post', ['id' => 'amendmentUpdateForm']);

echo '<div class="content form-horizontal">';

echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="amendmentStatus">';
echo 'Status';
echo ':</label><div class="col-md-4">';
$options = ['class' => 'form-control', 'id' => 'amendmentStatus'];
echo Html::dropDownList('amendment[status]', $amendment->status, Amendment::getStati(), $options);
echo '</div><div class="col-md-5">';
$options = ['class' => 'form-control', 'id' => 'amendmentStatusString', 'placeholder' => '...'];
echo Html::textInput('amendment[statusString]', $amendment->statusString, $options);
echo '</div></div>';




echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="amendmentTitlePrefix">';
echo 'Antragskürzel';
echo ':</label><div class="col-md-4">';
$options = [
    'class' => 'form-control',
    'id' => 'amendmentTitlePrefix',
    'placeholder' => 'z.B. "Ä1", "Ä1neu", "A23-0042"'
];
echo Html::textInput('amendment[titlePrefix]', $amendment->titlePrefix, $options);
echo '<small>Muss eindeutig sein.</small>';
echo '</div></div>';


$locale = Tools::getCurrentDateLocale();

$date = Tools::dateSql2bootstraptime($amendment->dateCreation);
echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="amendmentDateCreation">';
echo 'Angelegt am';
echo ':</label><div class="col-md-4"><div class="input-group date" id="amendmentDateCreationHolder">';
echo '<input type="text" class="form-control" name="amendment[dateCreation]" id="amendmentDateCreation"
                value="' . Html::encode($date) . '" data-locale="' . Html::encode($locale) . '">
            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>';
echo '</div></div></div>';

$date = Tools::dateSql2bootstraptime($amendment->dateResolution);
echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="amendmentDateResolution">';
echo 'Beschlossen am';
echo ':</label><div class="col-md-4"><div class="input-group date" id="amendmentDateResolutionHolder">';
echo '<input type="text" class="form-control" name="amendment[dateResolution]" id="amendmentDateResolution"
                value="' . Html::encode($date) . '" data-locale="' . Html::encode($locale) . '">
            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>';
echo '</div></div></div>';


echo '<div class="form-group">';
echo '<label class="col-md-3 control-label" for="amendmentNoteInternal">';
echo 'Interne Notiz';
echo ':</label><div class="col-md-9">';
$options = ['class' => 'form-control', 'id' => 'amendmentNoteInternal'];
echo Html::textarea('amendment[noteInternal]', $amendment->noteInternal, $options);
echo '</div></div>';

echo '</div>';




if (!$amendment->textFixed) {
    echo '<h2 class="green">' . 'Text bearbeiten' . '</h2>
<div class="content" id="amendmentTextEditCaller">
    <button type="button" class="btn btn-default">Bearbeiten</button>
</div>
<div class="content" id="amendmentTextEditHolder" style="display: none;">';

    foreach ($form->sections as $section) {
        if ($section->consultationSetting->type == \app\models\sectionTypes\ISectionType::TYPE_TITLE) {
            continue;
        }
        echo $section->getSectionType()->getAmendmentFormField();
    }

    echo '</div>';
}



$initiatorClass = $form->motion->motionType->getAmendmentInitiatorFormClass();
$initiatorClass->setAdminMode(true);
echo $initiatorClass->getAmendmentForm($form->motion->motionType, $form, $controller);



echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">Speichern</button>
</div>';

echo Html::endForm();

$layout->addOnLoadJS('$.AntragsgruenAdmin.amendmentEditInit();');
