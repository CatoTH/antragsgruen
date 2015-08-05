<?php

/**
 * @var $this yii\web\View
 * @var Consultation $consultation
 * @var string $locale
 */
use app\components\UrlHelper;
use app\models\db\Consultation;
use yii\helpers\Html;

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->addJS('/js/backend.js');
$layout->addCSS('/css/backend.css');

$this->title = 'Einstellungen';
$layout->addBreadcrumb('Administration', UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb('Veranstaltung');

echo '<h1>Einstellungen</h1>';
echo Html::beginForm('', 'post', ['id' => 'consultationSettingsForm', 'class' => 'adminForm form-horizontal']);

echo $controller->showErrors();

$settings            = $consultation->getSettings();
$siteSettings        = $consultation->site->getSettings();
$handledSettings     = [];
$handledSiteSettings = [];


echo '<h2 class="green">Allgemeine Einstellungen zur Veranstaltung</h2>';
echo '<div class="content">';
$handledSettings[] = 'maintainanceMode';
echo '<div>
        <label>';
echo Html::checkbox('settings[maintainanceMode]', $settings->maintainanceMode, ['id' => 'maintainanceMode']);
echo '<strong>Wartungsmodus aktiv</strong>
            <small>(Nur Admins können den Seiteninhalt sehen)</small>
        </label>
    </div>';

$handledSettings[] = 'startLayoutType';
echo '<div class="form-group">
        <label class="col-sm-4 control-label" for="startLayoutType">Startseiten-Design:</label>
        <div class="col-sm-8">';
echo Html::dropDownList(
    'settings[startLayoutType]',
    $consultation->getSettings()->startLayoutType,
    $consultation->getSettings()->getStartLayouts(),
    ['id' => 'startLayoutType', 'class' => 'form-control']
);
echo '</div></div>';


echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="consultationPath">Verzeichnis:</label>
    <div class="col-sm-8 urlPathHolder">
        <div class="shower">' . Html::encode($consultation->urlPath) . ' [<a href="#">ändern</a>]</div>
        <div class="holder hidden">
        <input type="text" required name="consultation[urlPath]"
        value="' . Html::encode($consultation->urlPath) . '" class="form-control" id="consultationPath">
        <small>Hinweis: mit dieser Angabe ändern sich auch alle Links auf Anträge etc.</small>
        </div>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-4 control-label" for="consultationTitle">' . 'Titel der Veranstaltung' . ':</label>
    <div class="col-sm-8">
    <input type="text" required name="consultation[title]" ' .
    'value="' . Html::encode($consultation->title) . '" class="form-control" id="consultationTitle">
    </div>
</div>

<div class="form-group">
    <label class="col-sm-4 control-label" for="consultationTitleShort">' . 'Kurzversion' . ':</label>
    <div class="col-sm-8">
    <input type="text" required name="consultation[titleShort]" ' .
    'value="' . Html::encode($consultation->titleShort) . '" class="form-control" id="consultationTitleShort">
    </div>
</div>';

echo '</div>

<h2 class="green">Anträge</h2>
<div class="content">';

$handledSettings[] = 'lineNumberingGlobal';
echo '<div><label>';
echo Html::checkbox('settings[lineNumberingGlobal]', $settings->lineNumberingGlobal, ['id' => 'lineNumberingGlobal']);
echo '<strong>Zeilennummerierung</strong> durchgehend für die ganze Veranstaltung
    </label></div>';


$handledSettings[] = 'screeningMotions';
echo '<div><label>';
echo Html::checkbox('settings[screeningMotions]', $settings->screeningMotions, ['id' => 'screeningMotions']);
echo '<strong>Freischaltung</strong> von Anträgen
    </label></div>';

$layout->addOnLoadJS('$.AntragsgruenAdmin.consultationEditForm();');


echo '</div>
<h2 class="green">Änderungsanträge</h2>
<div class="content">';

/*
<div class="form-group">
        <label class="col-sm-4 control-label" for="policyAmendments">Antragsberechtigt:</label>
        <div class="col-sm-8">';
echo Html::dropDownList(
    'consultation[policyAmendments]',
    $consultation->policyAmendments,
    IPolicy::getPolicyNames(),
    ['id' => 'policyAmendments', 'class' => 'form-control']
);
echo '</div></div>';
*/

echo '<div class="form-group">
        <label class="col-sm-4 control-label" for="amendmentNumbering">Nummerierung:</label>
        <div class="col-sm-8">';
echo Html::dropDownList(
    'consultation[amendmentNumbering]',
    $consultation->amendmentNumbering,
    \app\models\amendmentNumbering\IAmendmentNumbering::getNames(),
    ['id' => 'amendmentNumbering', 'class' => 'form-control']
);
echo '</div></div>';


$handledSettings[] = 'screeningAmendments';
echo '<div><label>';
echo Html::checkbox('settings[screeningAmendments]', $settings->screeningAmendments, ['id' => 'screeningAmendments']);
echo '<strong>Freischaltung</strong> von Änderungsanträgen
    </label></div>';


echo '</div>

<h2 class="green">Kommentare</h2>

<div class="content">';
/*
<div class="form-group">
        <label class="col-sm-4 control-label" for="policyComments">Kommentieren dürfen:</label>
        <div class="col-sm-8">';
echo Html::dropDownList(
    'consultation[policyComments]',
    $consultation->policyComments,
    IPolicy::getPolicyNames(),
    ['id' => 'policyComments', 'class' => 'form-control']
);
echo '</div></div>';
*/

$handledSettings[] = 'screeningComments';
echo '<div><label>';
echo Html::checkbox('settings[screeningComments]', $settings->screeningComments, ['id' => 'screeningComments']);
echo '<strong>Freischaltung</strong> von Kommentaren
    </label></div>';


$handledSettings[] = 'commentNeedsEmail';
echo '<div><label>';
echo Html::checkbox('settings[commentNeedsEmail]', $settings->commentNeedsEmail, ['id' => 'commentNeedsEmail']);
echo 'Angabe der <strong>E-Mail-Adresse</strong> erzwingen
    </label></div>';


echo '</div>
<h2 class="green">E-Mails</h2>
<div class="content">


<div class="form-group">
    <label class="col-sm-4 control-label" for="adminEmail">Admins:</label>
    <div class="col-sm-8">
    <input type="text" required name="consultation[adminEmail]" ' .
    'value="' . Html::encode($consultation->adminEmail) . '" class="form-control" id="adminEmail">
</div></div>';

$handledSiteSettings[] = 'emailFromName';
echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="emailReplyTo">Absender-Name:</label>
    <div class="col-sm-8">
    <input type="text" required name="siteSettings[emailFromName]" placeholder="Standard: &quot;Antragsgrün&quot;" ' .
    'value="' . Html::encode($siteSettings->emailFromName) . '" class="form-control" id="emailFromName">
</div></div>';

$handledSiteSettings[] = 'emailReplyTo';
echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="emailReplyTo">Reply-To:</label>
    <div class="col-sm-8">
    <input type="email" name="siteSettings[emailReplyTo]" placeholder="Im Zweifelsfall einfach leer lassen" ' .
    'value="' . Html::encode($siteSettings->emailReplyTo) . '" class="form-control" id="emailReplyTo">
</div></div>';


$handledSettings[] = 'initiatorConfirmEmails';
echo '<div><label>';
echo Html::checkbox(
    'settings[initiatorConfirmEmails]',
    $settings->initiatorConfirmEmails,
    ['id' => 'initiatorConfirmEmails']
);
echo 'Beim Anlegen/Freischalten eines Antrags: Bestätigungs-E-Mail an die AntragstellerIn schicken
    </label></div>';


echo '
<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">Speichern</button>
</div>


</div>';


foreach ($handledSettings as $setting) {
    echo '<input type="hidden" name="settingsFields[]" value="' . Html::encode($setting) . '">';
}
foreach ($handledSiteSettings as $setting) {
    echo '<input type="hidden" name="siteSettingsFields[]" value="' . Html::encode($setting) . '">';
}
echo Html::endForm();
