<?php

use app\components\UrlHelper;
use app\models\db\{Site, User};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün - die grüne Online-Antragsverwaltung';
/** @var \app\controllers\Base $controller */
$controller  = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl      = 'https://antragsgruen.de/';
$controller->layoutParams->alternateLanuages = ['en' => 'https://motion.tools/'];
$controller->layoutParams->addInlineCss('
    .homeFigure { text-align: center; }
    .homeFigure figcaption { margin-top: -20px; margin-bottom: 20px; font-size: 0.8em; font-style: italic; }
    .homeFigureAmendment img { max-width: 100%; }
    @media (min-width: 800px) {
        .homeFigureAmendment img { max-width: 600px; }
    }
    .homeFigurePrint { max-width: 230px; box-shadow: 0 0 7px rgba(0,0,0,.4); border-radius: 2px; overflow: hidden; }
    @media (min-width: 800px) {
        .homeFigurePrint { float: right; margin-left: 50px; }
    }
    @media (max-width: 799px) {
        .homeFigurePrint { margin: 20px auto; }
    }
    .homeFigurePrint img { max-width: 100%; }
    .homeFigurePrint figcaption { margin-bottom: 5px; }

    @media (min-width: 800px) {
        .homeFigureTwoHolder { display: flex; flex-direction: row; margin-bottom: 30px; margin-top: -25px; }
        .homeFigureTwoHolder > * { flex-basis: 50%; }
    }
    .homeFigureTwoHolder img { max-width: 100%; }
    .homeFigureSpeech figcaption { margin-top: -10px; }
');

?>
<h1 id="antragsgruenTitle">Antragsgrün - das grüne Antragstool</h1>

<?= $controller->layoutParams->getMiniMenu('sidebarSmall'); ?>

<section class="content infoSite" aria-labelledby="antragsgruenTitle">
    <p>
        Antragsgrün ist ein <strong>Antrags-Verwaltungs-System</strong>,
        das speziell für <strong>Mitgliederversammlungen,
        Parteitage, Verbandstagungen sowie Programmdiskussionen</strong> entwickelt wurde.
    </p>

    <p>
        Es hilft, eine größere Zahl von Anträgen, Änderungsanträgen und
        Kommentaren übersichtlich, nutzer*innenfreundlich und effizient darzustellen.
        Es ist hoch flexibel, kann an verschiedenste Bedürfnisse angepasst werden und wird kontinuierlich
        weiterentwickelt.
    </p>

    <p>
        Zum Einsatz kommt es unter anderem bei Parteitagen, von Bundesdelegiertenkonferenzen
        bis hin zu kommunalen Programmparteitagen, in Diözesen,
        sowie bei Jugendorganisationen wie dem Deutschen Bundesjugendring.
    </p>
</section>

<section aria-labelledby="antraege">
    <h2 id="antraege" class="green">Anträge, Änderungsanträge, Bewerbungen</h2>

    <div class="content infoSite">
        <p>Antragsgrün ermöglicht es, das Antragswesen inklusive Änderungsanträgen abzubilden.</p>

        <p><strong>Anträge, Satzungen, Positionspapiere oder Wahlprogramme</strong> können eingereicht und veröffentlicht werden,
            wahlweise von allen Mitgliedern oder eingeschränkten Kreisen wie dem Vorstand oder Delegierten.
            Auch das Einreichen von <strong>Bewerbungen</strong>, inklusive Bild- und PDF-Upload wird unterstützt.</p>

        <figure class="homeFigure homeFigureAmendment">
            <img src="/img/Screenshot-Amendment-de.png" alt="Screenshot eines Änderungsantrags">
            <figcaption>
                Änderungsanträge können sowohl separat als auch (wie hier) im Kontext des Antrags angezeigt werden
            </figcaption>
        </figure>

        <p>Neben einer <strong>Kommentarfunktion</strong> für so veröffentlichte Dokumente bietet Antragsgrün
            vor allem die Möglichkeit, <strong>Änderungsanträge</strong> einfach einzureichen,
            die von der Antragskommission oder der Mitgliederversammlung behandelt werden.</p>

        <figure class="homeFigure homeFigurePrint">
            <img src="/img/Screenshot-Print.png" alt="Druckvorlage">
            <figcaption>
                Vielfältige Exports, wie z.B. Druckvorlagen
            </figcaption>
        </figure>

        <p><strong>Antragsgrün unterstützt die Veranstaltungsleitung</strong> dabei durch eine Vielzahl an Funktionen:</p>

        <ul>
            <li>Druckvorlagen können automatisch erzeugt werden<br>(Export als PDF, Spreadsheet oder Textdokumente)</li>
            <li>Angenommene Änderungsanträge können einfach in den ursprünglichen Text eingearbeitet werden</li>
            <li>E-Mail-Benachrichtigungen sowohl für Administrierende als auch für Teilnehmende</li>
            <li>Festlegen von Zuständigkeiten, interne Übersichten und Kommentare</li>
            <li>... und viel mehr.</li>
        </ul>

        <p>
            <a href="<?= Html::encode(UrlHelper::createUrl('manager/help')) ?>" style="font-weight: bold;">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                Zur ausführlichen Funktionsbeschreibung und Hilfe
            </a><br><br>
            <a href="/help/member-motion">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                Tutorial: Anträge einreichen
            </a><br>
            <a href="/help/amendments">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                Tutorial: Änderungsanträge einreichen
            </a><br>
        </p>

        <br style="clear: both;">
    </div>
</section>

<section aria-labelledby="vorort">
    <h2 id="vorort" class="green">Vor Ort auf Versammlungen</h2>

    <div class="content infoSite">
        <p>Antragsgrün unterstützt die Antragsarbeit vor Ort auf Veranstaltungen:</p>

        <ul>
            <li>Eine einfache Verwaltung der <strong>Tagesordnung</strong></li>
            <li><strong>Redelisten</strong>, sowohl Einfache als auch Quotierte</li>
            <li><strong>Anwesenheitslisten und Abstimmungen</strong> über Anträge und Änderungsanträge</li>
            <li><strong>Projektor</strong>-geeignete Vollbilddarstellung aller wichtigen Inhalte</li>
        </ul>
    </div>


    <div class="homeFigureTwoHolder">
        <figure class="homeFigure homeFigureSpeech">
            <img src="/img/Screenshot-Redeliste.png" alt="Screenshot einer Redeliste">
            <figcaption>
                Flexible Redelisten mit einfacher Verwaltung
            </figcaption>
        </figure>

        <figure class="homeFigure homeFigureVoting">
            <img src="/img/Screenshot-Abstimmungen.png" alt="Screenshot einer Abstimmung">
            <figcaption>
                Konfigurierbare Abstimmungen über Anträge und Fragestellungen
            </figcaption>
        </figure>
    </div>
</section>

<section aria-labelledby="flexibel">
    <h2 id="flexibel" class="green">Flexibel und Anpassbar</h2>

    <div class="content infoSite">
        <p>Antragsgrün wird bereits von verschiedensten Organisationen für unterschiedliche Zwecke eingesetzt
            und lässt sich daher bereits standardmäßig für viele Szenarien anpassen. Beispielsweise:</p>

        <ul>
            <li>Das <strong>Layout</strong> der Seite kann über eine einfache Web-Oberfläche angepasst werden: anpassbar sind sowohl Farben, Logo, Beschriftungen und die erzeugten Druckvorlagen.</li>
            <li>Das <strong>Einreichen</strong> von Anträgen und Änderungsanträgen kann an verschiedene Voraussetzungen geknüpft werden: an einen Antragsschluss, an bestimmte Berechtigungen, genügend Unterstützer*innen, Vorab-Prüfung durch eine Antragskommission, …</li>
            <li>Eine Integration in existierende <strong>Single-Sign-On-Mechanismen</strong> (wie z.B. SAML) ist vorgesehen und kann auf Auftragsbasis implementiert werden</li>
        </ul>

        <p>Für weiter gehende und Organisations-spezifische Anpassungen bieten wir <strong>professionelle Unterstützung auf Auftragsbasis.</strong></p>

        <p><strong>Ansprechpartner für Anpassungen</strong>:<br>
            Tobias Hößl<br>
            <a href="mailto:info@antragsgruen.de">info@antragsgruen.de</a><br>
            <a href="tel:+4915156024223">+49 151 56024223</a>
    </div>
</section>

<section aria-labelledby="ausgereift">
    <h2 id="ausgereift" class="green">Ausgereift, Offen, Privatsphären-freundlich</h2>

    <div class="content infoSite">
        <p>Antragsgrün wird <strong>seit über zehn Jahren</strong> auf Parteitagen, Mitgliederversammlungen, Arbeitskreisen
            und Fraktionen eingesetzt und hat sich sowohl bei kurzfristigen internen Abstimmungen als auch bei
            Programmparteitagen mit über tausend Anträgen und hunderten Delegierten bewährt.</p>

        <p>Antragsgrün wird als <strong>Open Source</strong> (AGPL) kontinuierlich in Kooperation mit den nutzenden
            Organisationen weiterentwickelt. Antragsgrün kann damit einerseits als fertiges Paket einfach
            installiert und kostenlos genutzt werden. Den Download und eine Anleitung
            zur Installation gibt es auf <a href="https://github.com/CatoTH/antragsgruen">Github</a>.

        <p>Andererseits bieten wir auch <strong>professionellen Support</strong>,
            Hosting, die Umsetzung neuer Funktionen sowie mandantenspezifische Anpassungen auf Auftragsbasis an.</p>

        <p>Bei allen Funktionen liegt uns der Datenschutz besonders am Herzen:
            wir sammeln keine unnötigen Daten, setzen <strong>keine Tracker</strong> ein,
            schalten keine Werbung und alle unsere Server befinden sich innerhalb der EU.</p>
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
            kleineren bis mittelgroßen Organisationen bieten wir das Hosting in der Regel für 100€ + MwSt. pro Jahr an.
            Jugendorganisationen für die Hälfte.
        </p>

        <h3>Angebot für Grüne Organisationen</h3>
        <p style="margin-bottom: 40px;">
            Grünen bzw. Grünen-nahen Organisationen bieten wir an, sich selbstständig eine <strong>Unterseite auf antragsgruen.de</strong>
            einzurichten, um dort ihre Parteitage / Programmdiskussionen zu organisieren.<br>
            Dazu kannst du das Formular unter
            „<a href="#opensource" onClick="$('#asGreenMember').scrollintoview({top_offset: -50}); return false;">Als
                Grünen-Mitglied nutzen</a>“ nutzen und es dir innerhalb von zwei, drei Minuten selbst einrichten.
            Benötigt wird nur ein Zugang beim Grünen Netz.<br>
            Die oben genannte Hosting-Gebühr entfällt in diesem Fall.
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
            \app\plugins\gruene_de_saml\SamlLogin::createGruenesNetzLoginUrl('/manager/createsite'),
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
Du wirst, nachdem du hier deinen Benutzer*innenname eingegeben hast, auf eine Login-Seite umgeleitet, die vom
grünen Bundesverband betrieben wird (Adresse im Browser: https://saml.gruene.de/). Dort wirst du aufgefordert,
deinen Benutzer*innenname und -Passwort des Grünen Netzes einzugeben. Diese Seite bestätigt
gegenüber Antragsgrün, dass du Parteimitglied bist und leitet deinen Namen und E-Mail-Adresse weiter - nicht
aber das Passwort.</div>';

        echo Html::endForm();
    }
    echo '</div>';
    ?>
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
