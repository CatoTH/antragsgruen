<?php
use app\components\UrlHelper;
use app\models\db\Site;
use app\models\db\User;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 * @var \app\controllers\Base $controller
 */

$this->title = 'Antragsgrün - die grüne Online-Antragsverwaltung';
$controller  = $this->context;
$controller->layoutParams->addCSS('css/manager.css');

?>
<h1>Antragsgrün - das grüne Antragstool</h1>

<div class="content infoSite">
    <p>Antragsgrün ist ein <strong>Antrags-Verwaltungs-System</strong>, das speziell für <strong>Parteitage,
            Verbandstagungen
            sowie Programmdiskussionen</strong> entwickelt wurde.</p>

    <p>Es hilft, eine größere Zahl von Anträgen, Änderungsanträgen und
        Kommentaren übersichtlich, nutzer*innenfreundlich und effizient darzustellen. Zum Einsatz kommt es vor allem bei
        grünen Parteitagen, von der BDK bis hin zu kommunalen Programmparteitagen, sowie dem Deutschen Bundesjugendring.
        Es ist hoch flexibel, kann an verschiedenste Bedürfnisse angepasst werden und wird kontinuierlich
        weiterentwickelt.
    </p>
</div>

<h2 id="funktionen" class="green">Welche Funktionen bietet Antragsgrün?</h2>

<div class="content infoSite">
    <strong>Das kann Antragsgrün:</strong>
    <ul>
        <li><strong>Anträge, Änderungsanträge, Kommentare</strong> dazu, Unterstützen von (Änderungs-)Anträgen.
            Übersichtliche Darstellung von Änderungsanträgen.
        </li>
        <li><strong>Tagesordnungen</strong> werden unterstützt, mit unterschiedlichen Berechtigungen und
            Antragsformularen pro Tagesordnungspunkt.
        </li>
        <li>Beliebige <strong>Textformatierungen</strong> in redaktionellen Texten (u.a. auch YouTube/Vimeo-Videos,
            Grafiken etc.). Bei Anträgen und Änderungsanträgen sind einige Standard-Textformatierungen möglich.
        </li>
        <li>Automatisch erzeugte <strong>PDF</strong>-Versionen und <strong>Spreadsheet-Listen</strong>
            der Anträge und Änderungsanträge.
        </li>
        <li><strong>Berechtigungen</strong>: Wer Anträge, Änderungsanträge und Kommentare verfassen darf, lässt sich
            jeweils festlegen. Niemand / nur Admins, Alle, oder nur eingeloggte Nutzer*innen.
        </li>
        <li>Ein effizientes Backend für die Antragskommission zum <strong>Moderieren</strong> von (Änderungs-)Anträgen
            oder Kommentaren.
        </li>
        <li>Hohe <strong>Anpassbarkeit</strong>: Die Antrags- und Unterstützer*innen-Formulare sowie das „Wording“ lässt
            sich frei an die eigenen Bedürfnisse anpassen. Es stehen unterschiedliche Layout-Varianten zur
            Auswahl, Varianten zur Nummerierung von (Änderungs-)Anträgen usw. Wir versuchen, die gesamte grüne
            Vielfalt abzubilden :-).
        </li>
        <li><strong>E-Mail-Benachrichtigungen</strong> über neue Anträge, Änderungsanträge und/oder Kommentare für alle
            Interessierte
        </li>
        <li>RSS-Feeds, damit alle Interessierte über neu eingereichte (Änderungs-)Anträge oder Kommentare auf dem
            Laufenden bleiben.
        </li>
    </ul>

    <strong>Das kann Antragsgrün nicht</strong>:
    <ul>
        <li><strong>Vor-Ort-Präsentationen</strong>. Auf Parteitagen selbst bietet sich der
            Einsatz von Tools an, die speziell dafür ausgelegt sind - wir empfehlen hier <a
                href="http://openslides.org/de/">OpenSlides</a>.
            (Antragsgrün bietet die Möglichkeit, Anträge und Änderungsanträge in Openslides zu exportieren.)
        </li>
        <li><strong>Wahlen / Abstimmungen</strong>.</li>
    </ul>


</div>

<?php
echo '<h2 id="selbst_nutzen" class="green">Antragsgrün nutzen</h2>

<div class="content infoSite">

<p>Antragsgrün ist <strong><a href="#opensource" onClick="$(\'#opensource\').scrollintoview({top_offset: -50}); return false;">OpenSource-Software</a></strong>
und kann von jeder und jedem frei genutzt werden, sowohl um an Diskussionen teilzunehmen, als auch um eigene Programm-/Antragsdiskussionen einzurichten.</p>
<p>Grünen bzw. Grünen-nahen Organisationen bieten wir an, sich eine <strong>Unterseite auf antransgruen.de</strong> einzurichten und dort ihre Parteitage / Programmdiskussionen zu organisieren. Dazu kannst du das Formular unter "<a href="#opensource" onClick="$(\'#asGreenMember\').scrollintoview({top_offset: -50}); return false;">Als Grünen-Mitglied nutzen</a>" nutzen und es dir innerhalb von zwei, drei Minuten selbst einrichten - kostenlos (wobei wir uns über einen kleinen freiwilligen Beitrag zum Betrieb und der Weiterentwicklung freuen würden). Benötigt wird nur ein Wurzelwerk-Zugang.</p>
<p>Sind speziellere <strong>programmiertechnische Anpassungen</strong> nötig, oder sollen wir Antragsgrün auf einer <strong>eigenen Domain</strong> hosten, können wir diese auf Stundensatzbasis umsetzen. Bei Fragen und Wünschen sind wir immer
<a href="#wer" onClick="$(\'#wer\').scrollintoview({top_offset: -50}); return false;">erreichbar</a>.
</div>

<h2 class="green" id="asGreenMember">Als Grünen-Mitglied nutzen</h2>
<div class="content infoSite">';

if (User::getCurrentUser()) {
    $url = Html::encode(UrlHelper::createUrl('manager/createsite'));
    echo '<form method="GET" action="' . $url . '" class="siteCreateForm">
        <button type="submit" class="btn btn-success">
        <span class="glyphicon glyphicon-chevron-right"></span> Seite anlegen</button></form>';
} else {
    echo Html::beginForm(
        UrlHelper::createWurzelwerkLoginUrl('manager/createsite'),
        'post',
        [
            'class' => 'form-inline',
            'style' => 'margin-top: 20px;'
        ]
    );
    echo '
        Um dir sofort eine eigene Version von Antragsgrün einzurichten, logge dich zunächst mit deinem
    Wurzelwerk-Account ein.<br><br>
<div class="form-group">
        <label for="wwoauth" style="vertical-align: top; margin-top: 5px;">
            Wurzelwerk-BenutzerInnenname<br>
            <a href="https://netz.gruene.de/passwordForgotten.form" target="_blank" style="font-size: 0.8em;
            margin-top: -7px; display: inline-block; margin-bottom: 10px; font-weight: normal;">
                Wurzelwerk-Zugangsdaten vergessen?</a>
        </label>
        <input type="text" class="form-control" id="wwoauth" name="username" placeholder="Jane Doe">
    </div>
    <button type="submit" class="btn btn-primary" name="login_do" style="vertical-align: top;">Einloggen</button>';

    echo '<div class="privacyHint"><strong>Erklärung / Datenschutz:</strong><br>
Du wirst, nachdem du hier deinen BenutzerInnenname eingegeben hast, auf eine "OpenID"-Seite umgeleitet, die vom
grünen Bundesverband betrieben wird (Adresse im Browser: https://service.gruene.de). Dort wirst du (ggf. auf
englisch) aufgefordert, deinen Wurzelwerk-BenutzerInnenname und -Passwort einzugeben. Diese Seite bestätigt
gegenüber Antragsgrün, dass du Parteimitglied bist und leitet deinen Namen und E-Mail-Adresse weiter - nicht
aber das Wurzelwerk-Passwort.</div>';

    echo Html::endForm();
}
echo '</div>';

?>
<h2 id="wer" class="green">Kontakt</h2>

<div class="content infoSite">
    <p>Antragsgrün wird von „<strong>Netzbegrünung</strong> - Verein für GRÜNE Netzkultur“ betrieben. Programmiert wird
        es von <a href="https://www.hoessl.eu/">Tobias Hößl</a> (<a
            href="https://twitter.com/TobiasHoessl">@TobiasHoessl</a>), das Design stammt von <a
            href="http://www.netzminze.de/">Karin Wehle</a>.</p>

    <p>Wir werden das Antragsgrün in Zukunft weiter ausbauen und um <strong>zusätzliche Funktionen</strong> ergänzen.
        Funktionen, für die sich „Sponsoren“ finden, werden dabei besonders priorisiert.
    </p>

    <p>Ihr könnt uns bevorzugt per <strong>E-Mail</strong> unter
        <a href="mailto:info@antragsgruen.de">info@antragsgruen.de</a>
        erreichen, in dringenden Fällen auch telefonisch unter 0151-56024223.</p>
</div>


<h2 id="opensource" class="green">Open Source</h2>

<div class="content infoSite">
    <p>Wir Grüne bekennen uns schon lange zu freier Software, insofern ist es für uns selbstverständlich, dass wir
        Antragsgrün unter einer Open-Source-Lizenz zur Verfügung stellen.</p>

    <p>Der komplette Quellcode von Antragsgrün ist unter
        <a href="https://github.com/CatoTH/antragsgruen">https://github.com/CatoTH/antragsgruen</a>
        abrufbar.</p>

    <p>Antragsgrün steht unter der „GNU Affero General Public License“. Das heißt, jede und jeder Interessierte kann das
        Tool nicht nur kostenlos einsetzen, sondern bei Bedarf auch Änderungen vornehmen (was auch die Verwendung in
        anderen Parteien oder völlig anderen Organisationen umfasst). Bedingung dafür ist aber unter anderem, dass wir
        als UrheberInnen weiter genannt werden und Änderungen am Tool ebenfalls wieder frei verfügbar gemacht
        werden.</p>
</div>

