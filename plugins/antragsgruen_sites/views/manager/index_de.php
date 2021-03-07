<?php

use app\components\UrlHelper;
use app\models\db\{Site, User};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 * @var \app\controllers\Base $controller
 */

$this->title = 'Antragsgrün - die grüne Online-Antragsverwaltung';
$controller  = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl      = 'https://antragsgruen.de/';
$controller->layoutParams->alternateLanuages = ['en' => 'https://motion.tools/'];

/** @var \app\models\settings\AntragsgruenApp $params */
$params = \Yii::$app->params;

?>
<h1 id="antragsgruenTitle">Antragsgrün - das grüne Antragstool</h1>

<section class="content infoSite" aria-labelledby="antragsgruenTitle">
    <p>Antragsgrün ist ein <strong>Antrags-Verwaltungs-System</strong>, das speziell für <strong>Parteitage,
            Verbandstagungen sowie Programmdiskussionen</strong> entwickelt wurde.</p>

    <p>
        Es hilft, eine größere Zahl von Anträgen, Änderungsanträgen und
        Kommentaren übersichtlich, nutzer*innenfreundlich und effizient darzustellen.
        Es ist hoch flexibel, kann an verschiedenste Bedürfnisse angepasst werden und wird kontinuierlich
        weiterentwickelt.
    </p>

    <p>
        Zum Einsatz kommt es unter anderem bei Parteitagen, von Bundesdelegiertenkonferenzen
        bis hin zu kommunalen Programmparteitagen,
        sowie bei Jugendorganisationen wie dem Deutschen Bundesjugendring.
    </p>
</section>

<section aria-labelledby="funktionen">
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
                Auswahl, Farben und Logos können ausgetauscht werden.
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
                Einsatz von Tools an, die speziell dafür ausgelegt sind - wir empfehlen hier
                <a lang="en" href="http://openslides.org/de/">OpenSlides</a>.
            </li>
            <li><strong>Wahlen / Abstimmungen</strong>.</li>
        </ul>

        <p style="text-align: center; font-weight: bold;">
            <a href="<?= Html::encode(UrlHelper::createUrl('manager/help')) ?>">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                Zur ausführlichen Funktionsbeschreibung und Hilfe
            </a>
        </p>
    </div>
</section>

<section aria-labelledby="selbst_nutzen">
    <h2 id="selbst_nutzen" class="green">Antragsgrün nutzen</h2>

    <div class="content infoSite">
        <h3>Antragsgrün ausprobieren</h3>
        <p>
            Wenn du erst einmal ausprobieren willst, ob Antragsgrün für deine Zwecke passend ist,
            kannst du hier schnell und ohne Angabe von Kontaktdaten eine eigene Test-Version von Antragsgrün
            anlegen, die für wenige Tage verfügbar ist.
        </p>
        <p style="text-align: center; margin-bottom: 40px;">
            <a href="https://sandbox.motion.tools/createsite?language=de" class="btn btn-default">Test-Version anlegen</a>
        </p>

        <h3>Hosting unter *.antragsgruen.de</h3>
        <p>
            Organisationen, die Antragsgrün nicht auf einem eigenen Server installieren und betreiben wollen, können unser Hosting in Anspruch nehmen.
            In diesem Fall bekommt man eine frei wählbare Subdomain, z.B. <em>meine-organisation.antragsgruen.de</em>,
            und kann darunter beliebig viele Veranstaltungen abhalten.
        </p>
        <p style="margin-bottom: 40px;">
            <a href="mailto:info@antragsgruen.de" aria-label="Kontaktiert uns - E-Mail an info@antragsgruen.de schreiben"><strong>Kontaktiert uns</strong></a>
            für genauere Informationen und ein konkretes Angebot -
            für kleinere bis mittelgroße Organisationen bieten wir das Hosting in der Regel für 100€ + MwSt. pro Jahr an.
            Für Jugendorganisationen für die Hälfte.
        </p>

        <h3>Angebot für Grüne Organisationen</h3>
        <p style="margin-bottom: 40px;">
            Grünen bzw. Grünen-nahen Organisationen bieten wir an, sich selbstständig eine <strong>Unterseite auf antragsgruen.de</strong>
            einzurichten, um dort ihre Parteitage / Programmdiskussionen zu organisieren.<br>
            Dazu kannst du das Formular unter
            "<a href="#opensource" onClick="$('#asGreenMember').scrollintoview({top_offset: -50}); return false;">Als
                Grünen-Mitglied nutzen</a>" nutzen und es dir innerhalb von zwei, drei Minuten selbst einrichten.
            Benötigt wird nur ein Zugang beim Grünen Netz.<br>
            Die oben genannte Hosting-Gebühr entfällt in diesem Fall - wir bitten aber dennoch um einen freiwilligen Beitrag,
            um Antragsgrün weiter betreiben zu können.
        </p>

        <h3>Fehlt eine Funktion? Professioneller Support und Anpassungen gewünscht?</h3>
        <p id="support" style="margin-bottom: 40px;">
            Sind speziellere programmiertechnische Anpassungen nötig, garantierte Verfügbarkeit während einem bestimmten
            Zeitraum, oder sollen wir Antragsgrün auf einer <strong>eigenen Domain</strong> hosten,
            können wir diese auf Stundensatzbasis umsetzen. Bei Fragen und Wünschen sind wir immer
            <a href="#wer" onClick="$('#wer').scrollintoview({top_offset: -50}); return false;">erreichbar</a>.
        </p>

        <h3>Antragsgrün ist <span lang="en">OpenSource</span></h3>
        <p style="margin-bottom: 40px;">
            Antragsgrün ist
            <a href="#opensource" onClick="$('#opensource').scrollintoview({top_offset: -50}); return false;">OpenSource-Software</a>
            und kann von jeder und jedem kostenlos genutzt werden, sowohl um an Diskussionen
            teilzunehmen, als auch um eigene Programm-/Antragsdiskussionen einzurichten. Den Download und eine Anleitung
            zur Installation gibt es auf <a href="https://github.com/CatoTH/antragsgruen">Github</a>.
        </p>
    </div>
</section>

<section aria-labelledby="asGreenMember">
    <?php
    echo '<h2 class="green" id="asGreenMember">Als Grünen-Mitglied nutzen</h2>
<div class="content infoSite">';

    if (User::getCurrentUser()) {
        $url = Html::encode(UrlHelper::createUrl('manager/createsite'));
        echo '<form method="GET" action="' . $url . '" class="siteCreateForm">
        <button type="submit" class="btn btn-success">
        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> Seite anlegen</button></form>';
    } else {
        echo Html::beginForm(
            UrlHelper::createGruenesNetzLoginUrl('manager/createsite'),
            'post',
            [
                'class' => 'form-inline login_saml',
                'style' => 'margin-top: 20px;'
            ]
        );
        echo '
        Um dir sofort eine eigene Version von Antragsgrün einzurichten, logge dich zunächst mit deinem
    &quot;Grünes Netz&quot;-Account ein.<br><br>';
        echo '<button type="submit" class="btn btn-primary" name="login_do" style="vertical-align: top;">Einloggen</button>';

        echo '<div class="privacyHint"><strong>Erklärung / Datenschutz:</strong><br>
Du wirst, nachdem du hier deinen Benutzer*innenname eingegeben hast, auf eine "<span aria-label="Open I.D." lang="en">OpenID</span>"-Seite umgeleitet, die vom
grünen Bundesverband betrieben wird (Adresse im Browser: https://service.gruene.de). Dort wirst du aufgefordert,
deinen Benutzer*innenname und -Passwort des Grünen Netzes einzugeben. Diese Seite bestätigt
gegenüber Antragsgrün, dass du Parteimitglied bist und leitet deinen Namen und E-Mail-Adresse weiter - nicht
aber das Passwort.</div>';

        echo Html::endForm();
    }
    echo '</div>';
    ?>
</section>

<section aria-labelledby="wer">
    <h2 id="wer" class="green">Kontakt</h2>

    <div class="content infoSite">
        <p>Antragsgrün wird von <a href="https://www.hoessl.eu/">Tobias Hößl</a> (<a
                href="https://twitter.com/TobiasHoessl">@TobiasHoessl</a>) programmiert, das ursprüngliche Design stammt von <a
                href="http://www.netzminze.de/">Karin Wehle</a>.</p>

        <p>Wir werden das Antragsgrün in Zukunft weiter ausbauen und um <strong>zusätzliche Funktionen</strong> ergänzen.
            Funktionen, für die sich „Sponsoren“ finden, werden dabei besonders priorisiert.
        </p>

        <p>Ihr könnt uns bevorzugt per <strong>E-Mail</strong> unter
            <a href="mailto:info@antragsgruen.de">info@antragsgruen.de</a>
            erreichen, in dringenden Fällen auch telefonisch unter 0151-56024223.</p>
    </div>
</section>

<section aria-labelledby="opensource">
    <h2 id="opensource" class="green" lang="en">Open Source</h2>

    <div class="content infoSite">
        <p>Antragsgrün steht unter der „<span lang="en">GNU Affero General Public License</span>“. Das heißt, jede und jeder Interessierte kann das
            Tool nicht nur kostenlos einsetzen, sondern bei Bedarf auch Änderungen vornehmen (was auch die Verwendung in
            anderen Parteien oder völlig anderen Organisationen umfasst). Bedingung dafür ist aber unter anderem, dass wir
            als Urheber*innen weiter genannt werden und Änderungen am Tool ebenfalls wieder frei verfügbar gemacht
            werden.</p>

        <p>Der komplette Quellcode von Antragsgrün ist unter
            <a href="https://github.com/CatoTH/antragsgruen" aria-label="Quellcode von Antragsgrün auf Github">https://github.com/CatoTH/antragsgruen</a>
            abrufbar.</p>
    </div>
</section>
