<?php

/**
 * @var \yii\web\View $this
 * @var Consultation $consultation
 * @var string $locale
 */
use app\components\UrlHelper;
use app\models\db\Consultation;
use yii\helpers\Html;

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
/** @var \app\models\settings\AntragsgruenApp $params */
$params = \Yii::$app->params;

$layout->addJS('js/backend.js');
$layout->addCSS('css/backend.css');
$layout->addJS('js/bower/Sortable/Sortable.min.js');
$layout->loadFuelux();

$this->title = 'Einstellungen';
$layout->addBreadcrumb('Administration', UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb('Veranstaltung');

$layout->addOnLoadJS('$.AntragsgruenAdmin.consultationSettingsForm();');

/**
 * @param \app\models\settings\Consultation $settings
 * @param string $field
 * @param array $handledSettings
 * @param string $description
 */
$booleanSettingRow = function ($settings, $field, &$handledSettings, $description) {
    $handledSettings[] = $field;
    echo '<div><label>';
    echo Html::checkbox('settings[' . $field . ']', $settings->$field, ['id' => $field]);
    echo $description;
    echo '</label></div>';
};


echo '<h1>Einstellungen</h1>';
echo Html::beginForm('', 'post', ['id' => 'consultationSettingsForm', 'class' => 'adminForm form-horizontal fuelux']);

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


echo '<div class="form-group">
    <label class="col-sm-3 control-label" for="consultationPath">Verzeichnis:</label>
    <div class="col-sm-9 urlPathHolder">
        <div class="shower">' . Html::encode($consultation->urlPath) . ' [<a href="#">ändern</a>]</div>
        <div class="holder hidden">
        <input type="text" required name="consultation[urlPath]"
        value="' . Html::encode($consultation->urlPath) . '" class="form-control" id="consultationPath">
        <small>Hinweis: mit dieser Angabe ändern sich auch alle Links auf Anträge etc.</small>
        </div>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label" for="consultationTitle">' . 'Titel der Veranstaltung' . ':</label>
    <div class="col-sm-9">
    <input type="text" required name="consultation[title]" ' .
    'value="' . Html::encode($consultation->title) . '" class="form-control" id="consultationTitle">
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label" for="consultationTitleShort">' . 'Kurzversion' . ':</label>
    <div class="col-sm-9">
    <input type="text" required name="consultation[titleShort]" ' .
    'value="' . Html::encode($consultation->titleShort) . '" class="form-control" id="consultationTitleShort">
    </div>
</div>';


$handledSettings[] = 'pdfIntroduction';
$placeholder       = '26. Ordentliche Bundesdelegiertenkonferenz von BÜNDNIS 90/DIE GRÜNEN,' . "\n" .
    '01.-03. Dezember 2006. Kölnmesse, Köln-Deutz';
echo '<fieldset class="form-group">
    <label class="col-sm-3 control-label" for="pdfIntroduction">PDF-Einleitung:</label>
    <div class="col-sm-9">
        <textarea name="settings[pdfIntroduction]" class="form-control" id="pdfIntroduction"
        placeholder="' . Html::encode($placeholder) . '">' . $settings->pdfIntroduction . '</textarea>
    </div>
</fieldset>';


$handledSettings[] = 'lineLength';
echo '<fieldset class="form-group">
    <label class="col-sm-3 control-label" for="consultationPath">Maximale Zeilenlänge:</label>
    <div class="col-sm-3">
        <input type="number" required name="settings[lineLength]"
        value="' . Html::encode($settings->lineLength) . '" class="form-control" id="lineLength">
    </div>
</fieldset>';
// NICHT ändern, nachdem Anträge eingereicht wurden, weil sich dann die Zeilennummern ändern!


echo '</div>
<h2 class="green">Aussehen</h2>
<div class="content">';


$handledSettings[] = 'startLayoutType';
echo '<div class="form-group">
        <label class="col-sm-3 control-label" for="startLayoutType">Startseiten-Design:</label>
        <div class="col-sm-9">';
echo Html::dropDownList(
    'settings[startLayoutType]',
    $consultation->getSettings()->startLayoutType,
    $consultation->getSettings()->getStartLayouts(),
    ['id' => 'startLayoutType', 'class' => 'form-control']
);
echo '</div></div>';


$layout                = $consultation->site->getSettings()->siteLayout;
$handledSiteSettings[] = 'siteLayout';
echo '<fieldset class="form-group">
    <label class="col-sm-3 control-label" for="consultationPath">Layout:</label>
    <div class="col-sm-9">';
echo Html::dropDownList(
    'siteSettings[siteLayout]',
    $layout,
    \app\models\settings\Layout::getCssLayouts(),
    ['id' => 'siteLayout', 'class' => 'form-control']
);
echo '</div></fieldset>';


$handledSettings[] = 'logoUrl';
echo '<fieldset class="form-group">
    <label class="col-sm-3 control-label" for="consultationPath">Logo-URL:</label>
    <div class="col-sm-9">
        <input type="text" name="settings[logoUrl]"
        value="' . Html::encode($settings->logoUrl) . '" class="form-control" id="logoUrl">
    </div>
</fieldset>';
// <small>Im Regelfall einfach leer lassen. Falls eine URL angegeben wird, wird das angegebene Bild statt dem
// großen "Antragsgrün"-Logo angezeigt.

$handledSettings[] = 'logoUrlFB';
echo '<fieldset class="form-group">
    <label class="col-sm-3 control-label" for="consultationPath">Facebook-Bild:</label>
    <div class="col-sm-9">
        <input type="text" name="settings[logoUrlFB]"
        value="' . Html::encode($settings->logoUrlFB) . '" class="form-control" id="logoUrlFB">
    </div>
</fieldset>';
// <small>Dieses Bild erscheint, wenn etwas auf dieser Seite bei Facebook geteilt wird. Vorsicht: nachträglich
// ändern ist oft heikel, da FB viel zwischenspeichert.


$description = '<strong>Antragskürzel verstecken</strong><br>
<small style="margin-left: 20px; display: block;">(Antragskürzel wie z.B. "A1", "A2", "Ä1neu" etc.)
müssen zwar weiterhin angegeben werden, damit danach sortiert werden kann. Es wird aber nicht mehr angezeigt.
Das ist dann praktisch, wenn man eine eigene Nummerierung im Titel der Anträge vornimmt.</small>';

$booleanSettingRow($settings, 'hideTitlePrefix', $handledSettings, $description);


$booleanSettingRow($settings, 'showFeeds', $handledSettings, 'Feeds in der Sidebar anzeigen');

$description = '<strong>Minimalistische Ansicht</strong><br>
<small style="margin-left: 20px;">Der Login-Button und der Info-Header über den Anträgen werden versteckt.</small>';
$booleanSettingRow($settings, 'minimalisticUI', $handledSettings, $description);


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


$description = 'Admins dürfen Antrags-Texte <strong>nachträglich ändern</strong>.';
$booleanSettingRow($settings, 'adminsMayEdit', $handledSettings, $description);

$description = 'Antragsteller*innen dürfen Anträge <strong>nachträglich ändern</strong>.';
$booleanSettingRow($settings, 'iniatorsMayEdit', $handledSettings, $description);


$description = 'Anträge (ausgegraut) anzeigen, auch wenn sie noch nicht freigeschaltet sind';
$booleanSettingRow($settings, 'screeningMotionsShown', $handledSettings, $description);


$tags = $consultation->getSortedTags();
echo '<div class="form-group">
<div class="col-sm-3 control-label">Themen:</div>
<div class="col-sm-9">

<div class="pillbox" data-initialize="pillbox" id="tagsList">
    <ul class="clearfix pill-group" id="tagsListUl">';

foreach ($tags as $tag) {
    echo '<li class="btn btn-default pill" data-id="' . $tag->id . '">
        <span>' . Html::encode($tag->title) . '</span>
        <span class="glyphicon glyphicon-close"><span class="sr-only">Remove</span></span>
    </li>';
}
echo '<li class="pillbox-input-wrap btn-group">
                <a class="pillbox-more">and <span class="pillbox-more-count"></span> more...</a>
                <input type="text" class="form-control dropdown-toggle pillbox-add-item" placeholder="Neues Thema">
                <button type="button" class="dropdown-toggle sr-only">
                    <span class="caret"></span>
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <!--<ul class="suggest dropdown-menu" role="menu" data-toggle="dropdown" data-flip="auto"></ul>-->
            </li>
        </ul>
    </div>';

$handledSettings[] = 'allowMultipleTags';
echo Html::checkbox('settings[allowMultipleTags]', $settings->allowMultipleTags, ['id' => 'allowMultipleTags']);
echo 'Mehrere Themen pro Antrag möglich</label>
</div>
</div>';


echo '</div>
<h2 class="green">Änderungsanträge</h2>
<div class="content">';

echo '<div class="form-group">
        <label class="col-sm-3 control-label" for="amendmentNumbering">Nummerierung:</label>
        <div class="col-sm-9">';
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
        <label class="col-sm-3 control-label" for="policyComments">Kommentieren dürfen:</label>
        <div class="col-sm-9">';
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
    <label class="col-sm-3 control-label" for="adminEmail">Admins:</label>
    <div class="col-sm-9">
    <input type="text" name="consultation[adminEmail]" ' .
    'value="' . Html::encode($consultation->adminEmail) . '" class="form-control" id="adminEmail">
</div></div>';

$handledSiteSettings[] = 'emailFromName';
$placeholder           = str_replace('%NAME%', $params->mailFromName, \Yii::t('admin', 'cons_email_from_place'));
echo '<div class="form-group">
    <label class="col-sm-3 control-label" for="emailReplyTo">' .
    Html::encode(\Yii::t('admin', 'cons_email_from')) . ':</label>
    <div class="col-sm-9">
    <input type="text" name="siteSettings[emailFromName]" placeholder="' . Html::encode($placeholder) . '" ' .
    'value="' . Html::encode($siteSettings->emailFromName) . '" class="form-control" id="emailFromName">
</div></div>';

$handledSiteSettings[] = 'emailReplyTo';
echo '<div class="form-group">
    <label class="col-sm-3 control-label" for="emailReplyTo">Reply-To:</label>
    <div class="col-sm-9">
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
