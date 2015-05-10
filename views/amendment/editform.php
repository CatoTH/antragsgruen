<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var string $mode
 * @var \app\models\forms\AmendmentEditForm $form
 * @var \app\models\db\Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;

if ($form->motion->titlePrefix != '') {
    $title = Yii::t(
        'amend',
        $mode == 'create' ? 'Änderungsantrag zu %prefix% stellen' : 'Änderungsantrag zu %prefix% bearbeiten'
    );
    $this->title = str_replace('%prefix%', $form->motion->titlePrefix, $title);
} else {
    $this->title = Yii::t('amend', $mode == 'create' ? 'Änderungsantrag stellen' : 'Änderungsantrag bearbeiten');
}


$params->addJS('/js/ckeditor/ckeditor.js');
$params->addBreadcrumb($form->motion->titlePrefix, UrlHelper::createMotionUrl($form->motion));
$params->addBreadcrumb(Yii::t('amend', $mode == 'create' ? 'Änderungsantrag stellen' : 'Änderungsantrag bearbeiten'));

echo '<h1>' . Html::encode($this->title) . '</h1>';

echo '<div class="form content">';

echo '<br><div class="alert alert-info" role="alert">';
echo 'Ändere hier den Antrag so ab, wie du ihn gern sehen würdest.<br>';
echo 'Unter &quot;<strong>Begründung</strong>&quot; kannst du die Änderung begründen.<br>';
echo 'Falls dein Änderungsantrag Hinweise an die Pogrammkommission enthält, kannst du diese als ' .
    '&quot;<strong>Redaktionellen Antrag</strong>&quot; beifügen.';
echo '</div><br style="clear: both;">';


echo $controller->showErrors();

$motionPolicy = $form->motion->motionType->getMotionPolicy();
if ($motionPolicy::getPolicyID() != \app\models\policies\All::getPolicyID()) {
    echo '<fieldset>
                <legend>' . Yii::t('amend', 'Voraussetzungen für einen Antrag'), '</legend>
            </fieldset>';

    echo $motionPolicy->getOnCreateDescription();
}

if (\Yii::$app->user->isGuest) {
    echo '<div class="alert alert-warning jsProtectionHint" role="alert">';
    echo 'Um diese Funktion zu nutzen, muss entweder JavaScript aktiviert sein, oder du musst eingeloggt sein.';
    echo '</div>';
}

echo '</div>';




echo Html::beginForm(
    '',
    'post',
    ['id' => 'amendmentEditForm', 'class' => 'motionEditForm', 'enctype' => 'multipart/form-data']
);

echo '<h2 class="green">Neuer Antragstext</h2>';
echo '<div class="content">';
foreach ($form->sections as $section) {
    echo $section->getSectionType()->getAmendmentFormField();
}

echo '</div>';


echo '<h2 class="green">Begründung</h2>';

echo '<div class="content">';



echo '<fieldset class="form-group wysiwyg-textarea" data-maxLen="0" data-fullHtml="0" id="amendmentReasonHolder">';
echo '<label for="amendmentReason">' . Yii::t('amend', 'Begründung') . '</label>';

echo '<textarea name="amendmentReason"  id="amendmentReason" class="raw">';
echo Html::encode($form->reason) . '</textarea>';
echo '<div class="texteditor" id="amendmentReason_wysiwyg">';
echo $form->reason;
echo '</div>';
echo '</fieldset>';

echo '</div>';



$initiatorClass = $form->motion->motionType->getAmendmentInitiatorFormClass();
echo $initiatorClass->getAmendmentInitiatorForm($consultation, $form, $controller);




echo '<div class="submitHolder content"><button type="submit" name="save" class="btn btn-primary">';
echo '<span class="glyphicon glyphicon-chevron-right"></span> Weiter';
echo '</button></div>';

$params->addOnLoadJS('$.Antragsgruen.amendmentEditForm();');

echo Html::endForm();
