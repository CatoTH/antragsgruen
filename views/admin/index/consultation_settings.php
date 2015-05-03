<?php

/**
 * @var $this yii\web\View
 * @var Consultation $consultation
 * @var string $locale
 */
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\policies\IPolicy;
use yii\helpers\Html;

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->addJS('/js/backend.js');
$layout->addCSS('/css/backend.css');
$layout->addJS('/js/bower/moment/min/moment-with-locales.min.js');
$layout->addJS('/js/bower/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js');
$layout->addCSS('/js/bower/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css');


$this->title = 'Einstellungen';
$layout->addBreadcrumb('Administration', UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb('Veranstaltung');

echo '<h1>Einstellungen</h1>';
echo Html::beginForm('', 'post', ['id' => 'consultationSettingsForm', 'class' => 'adminForm form-horizontal']);

echo $controller->showErrors();

$settings = $consultation->getSettings();
$handledSettings = [];


echo '<h2>Allgemeine Einstellungen zur Veranstaltung</h2>';
echo '<div class="content">';
$handledSettings[] = 'maintainanceMode';
echo '<fieldset>
        <label>';
echo Html::checkbox('settings[maintainanceMode]', $settings->maintainanceMode, ['id' => 'maintainanceMode']);
echo '<strong>Wartungsmodus aktiv</strong>
            <small>(Nur Admins können den Seiteninhalt sehen)</small>
        </label>
    </fieldset>';

$handledSettings[] = 'startLayoutType';
echo '<fieldset class="form-group">
        <label class="col-sm-4 control-label" for="startLayoutType">Startseiten-Design:</label>
        <div class="col-sm-8">';
echo Html::dropDownList(
    'settings[startLayoutType]',
    $consultation->getSettings()->startLayoutType,
    $consultation->getSettings()->getStartLayouts(),
    ['id' => 'startLayoutType', 'class' => 'form-control']
);
echo '</div></fieldset>';


echo '<fieldset class="form-group">
    <label class="col-sm-4 control-label" for="consultationPath">Verzeichnis:</label>
    <div class="col-sm-8">
        <input type="text" required name="consultation[urlPath]"
        value="' . Html::encode($consultation->urlPath) . '" class="form-control" id="consultationPath">
    </div>
</fieldset>

<fieldset class="form-group">
    <label class="col-sm-4 control-label" for="consultationTitle">Name:</label>
    <div class="col-sm-8">
    <input type="text" required name="consultation[title]" ' .
    'value="' . Html::encode($consultation->title) . '" class="form-control" id="consultationTitle">
    </div>
</fieldset>

<fieldset class="form-group">
    <label class="col-sm-4 control-label" for="consultationTitleShort">Name (kurz):</label>
    <div class="col-sm-8">
    <input type="text" required name="consultation[titleShort]" ' .
    'value="' . Html::encode($consultation->titleShort) . '" class="form-control" id="consultationTitleShort">
    </div>
</fieldset>';

$deadlineMotions = Tools::dateSql2bootstraptime($consultation->deadlineMotions);
$deadlineAmendments = Tools::dateSql2bootstraptime($consultation->deadlineAmendments);

echo '<fieldset class="form-group">
    <label class="col-sm-4 control-label" for="deadlineMotions">Antragsschluss:</label>
    <div class="col-sm-8">
        <div class="input-group date" id="deadlineMotionsHolder">
            <input type="text" class="form-control" name="consultation[deadlineMotions]"
                value="' . Html::encode($deadlineMotions) . '" data-locale="' . Html::encode($locale) . '">
            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
        </div>
    </div>
</fieldset>

<fieldset class="form-group">
    <label class="col-sm-4 control-label" for="deadlineAmendments">Ä.Antragsschluss:</label>
    <div class="col-sm-8">
        <div class="input-group date" id="deadlineAmendmentsHolder">
            <input type="text" class="form-control" name="consultation[deadlineAmendments]"
                value="' . Html::encode($deadlineAmendments) . '" data-locale="' . Html::encode($locale) . '">
            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
        </div>
    </div>
</fieldset>

';
/*


    <div>
        <input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="wartungs_modus_aktiv">
        <?php echo $form->labelEx($einstellungen, 'antrag_einleitung', array("label" => "PDF-Antrags-Einleitung")); ?>
        <div class="std_content_col">
            <?php echo $form->textArea($einstellungen, 'antrag_einleitung'); ?>
            <br>
            <small>Steht im PDF unter "Antrag", also z.B. "an die LDK in Würzburg"</small>
        </div>
        <?php echo $form->error($einstellungen, 'antrag_einleitung'); ?>
    </div>
    <br>
</div>

<br>

*/

echo '</div>

<h2>Anträge</h2>
<div class="content">

<fieldset class="form-group">
        <label class="col-sm-4 control-label" for="policyMotions">Antragsberechtigt:</label>
        <div class="col-sm-8">';
echo Html::dropDownList(
    'consultation[policyMotions]',
    $consultation->policyMotions,
    IPolicy::getPolicyNames(),
    ['id' => 'policyMotions', 'class' => 'form-control']
);
echo '</div></fieldset>';
/*
    <div class="antragstypen_row">
        <label>Auswählbare Antragstypen</label>

        <div class="std_content_col"><?
            foreach (Antrag::$TYPEN as $id => $name) {
                echo '<label><input name="antrags_typen_aktiviert[]" value="' . $id . '" type="checkbox" ';
                if (!in_array($id, $einstellungen->antrags_typen_deaktiviert)) echo ' checked';
                echo '> ' . CHtml::encode($name) . '</label>';
            }
            ?></div>
    </div>
    <br>
    <br>
*/


$handledSettings[] = 'lineNumberingGlobal';
echo '<fieldset><label>';
echo Html::checkbox('settings[lineNumberingGlobal]', $settings->lineNumberingGlobal, ['id' => 'lineNumberingGlobal']);
echo '<strong>Zeilennummerierung</strong> durchgehend für die ganze Veranstaltung
    </label></fieldset>';


$handledSettings[] = 'screeningMotions';
echo '<fieldset><label>';
echo Html::checkbox('settings[screeningMotions]', $settings->screeningMotions, ['id' => 'screeningMotions']);
echo '<strong>Freischaltung</strong> von Anträgen
    </label></fieldset>';


$handledSettings[] = 'motionNeedsEmail';
echo '<fieldset><label>';
echo Html::checkbox('settings[motionNeedsEmail]', $settings->motionNeedsEmail, ['id' => 'motionNeedsEmail']);
echo 'Angabe der <strong>E-Mail-Adresse</strong> erzwingen
            <small>(Bei Anträgen und Änderungsanträgen)</small>
    </label></fieldset>';


$handledSettings[] = 'motionHasPhone';
echo '<fieldset><label>';
echo Html::checkbox('settings[motionHasPhone]', $settings->motionHasPhone, ['id' => 'motionHasPhone']);
echo 'Angabe der <strong>Telefonnummer</strong> ermöglichen
            <small>(Bei Anträgen und Änderungsanträgen)</small>
    </label></fieldset>';


$handledSettings[] = 'motionNeedsPhone';
echo '<fieldset class="motionNeedsPhoneHolder"><label>';
echo Html::checkbox('settings[motionNeedsPhone]', $settings->motionNeedsPhone, ['id' => 'motionNeedsPhone']);
echo 'Angabe der <strong>Telefonnummer</strong> erzwingen
            <small>(Bei Anträgen und Änderungsanträgen)</small>
    </label></fieldset>';

$layout->addOnLoadJS('$.AntragsgruenAdmin.consultationEditForm();');


echo '</div>
<h2>Änderungsanträge</h2>
<div class="content">


<fieldset class="form-group">
        <label class="col-sm-4 control-label" for="policyAmendments">Antragsberechtigt:</label>
        <div class="col-sm-8">';
echo Html::dropDownList(
    'consultation[policyAmendments]',
    $consultation->policyAmendments,
    IPolicy::getPolicyNames(),
    ['id' => 'policyAmendments', 'class' => 'form-control']
);
echo '</div></fieldset>';


echo '<fieldset class="form-group">
        <label class="col-sm-4 control-label" for="amendmentNumbering">Nummerierung:</label>
        <div class="col-sm-8">';
echo Html::dropDownList(
    'consultation[amendmentNumbering]',
    $consultation->amendmentNumbering,
    \app\models\amendmentNumbering\IAmendmentNumbering::getNames(),
    ['id' => 'amendmentNumbering', 'class' => 'form-control']
);
echo '</div></fieldset>';


$handledSettings[] = 'screeningAmendments';
echo '<fieldset><label>';
echo Html::checkbox('settings[screeningAmendments]', $settings->screeningAmendments, ['id' => 'screeningAmendments']);
echo '<strong>Freischaltung</strong> von Änderungsanträgen
    </label></fieldset>';


echo '</div>

<h2>Kommentare</h2>

<div class="content">

<fieldset class="form-group">
        <label class="col-sm-4 control-label" for="policyComments">Kommentieren dürfen:</label>
        <div class="col-sm-8">';
echo Html::dropDownList(
    'consultation[policyComments]',
    $consultation->policyComments,
    IPolicy::getPolicyNames(),
    ['id' => 'policyComments', 'class' => 'form-control']
);
echo '</div></fieldset>';


$handledSettings[] = 'screeningComments';
echo '<fieldset><label>';
echo Html::checkbox('settings[screeningComments]', $settings->screeningComments, ['id' => 'screeningComments']);
echo '<strong>Freischaltung</strong> von Kommentaren
    </label></fieldset>';


$handledSettings[] = 'commentNeedsEmail';
echo '<fieldset><label>';
echo Html::checkbox('settings[commentNeedsEmail]', $settings->commentNeedsEmail, ['id' => 'commentNeedsEmail']);
echo 'Angabe der <strong>E-Mail-Adresse</strong> erzwingen
    </label></fieldset>';


echo '</div>
<h2>Benachrichtigungen</h2>
<div class="content">


<fieldset class="form-group">
    <label class="col-sm-4 control-label" for="adminEmail">Admin-E-Mails:</label>
    <div class="col-sm-8">
    <input type="text" required name="consultation[adminEmail]" ' .
    'value="' . Html::encode($consultation->adminEmail) . '" class="form-control" id="adminEmail">
</div></fieldset>
';


$handledSettings[] = 'confirmEmails';
echo '<fieldset><label>';
echo Html::checkbox('settings[confirmEmails]', $settings->confirmEmails, ['id' => 'confirmEmails']);
echo 'Bestätigungs-E-Mails an die NutzerInnen schicken
    </label></fieldset>';


echo '</div>


<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">Speichern</button>
</div>


</div>';


foreach ($handledSettings as $setting) {
    echo '<input type="hidden" name=settingsFields[]" value="' . Html::encode($setting) . '">';
}

echo Html::endForm();
