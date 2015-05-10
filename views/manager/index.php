<?php
use app\components\UrlHelper;
use app\models\db\Site;
use app\models\db\User;
use yii\helpers\Html;

$controller = $this->context;

/**
 * @var $this yii\web\View
 * @var Site[] $site
 * @var \app\controllers\Base $controller
 */

$this->title = "Antragsgrün - die grüne Online-Antragsverwaltung";

?>
<h1>Antragsgrün - das Antragstool selbst einsetzen</h1>

<div class="content">
    <p>Antragsgrün ist <strong>Open Source</strong>, jede und jeder kann die Software herunterladen, für die eigenen
        Zwecke anpassen und verwenden (siehe <a href="#opensource"
                                                onclick="$('#opensource').next().scrollintoview(); return false;">weiter
            unten</a>).</p>

    <p><strong>Für Mitglieder der Grünen ist es noch einfacher</strong>: einfach weiter unten
        <a href="#selbst_nutzen" onclick="$('#selbst_nutzen').next().scrollintoview(); return false;">mit
            dem Wurzelwerk-BenutzerInnennamen einloggen</a>, ein paar Angaben zum Einsatzzweck machen, bei Bedarf noch
        einige Feineinstellungen vornehmen, und los!</p>

    <p>Antragsgrün wird von der <a href="http://www.netzbegruenung.de/"><strong>Netzbegrünung</strong></a> betrieben und
        kann kostenlos genutzt werden. Um einen freiwilligen Beitrag für den Betrieb wird aber sehr gebeten.</p>

    <p>Falls Antragsgrün noch nicht alle Funktionen erfüllt, die benötigt werden, können wir es auf Auftrag auch für
        deine Zwecke <strong>anpassen</strong>. <a href="#wer"
                                                   onclick="$('#wer').next().scrollintoview(); return false;">Einfach
            fragen!</a></p>
</div>

<h2 id="funktionen green">Welche Funktionen bietet Antragsgrün?</h2>

<div class="content">
    <strong>Das kann Antragsgrün:</strong>
    <ul style="margin-bottom: 25px;">
        <li style="margin-top: 7px;">Anträge, Änderungsanträge, Kommentare dazu, Unterstützen von (Änderungs-)Anträgen,
            Bewertung von Kommentaren. Alles außer die Anträge ist auch deaktivierbar.
        </li>
        <li style="margin-top: 7px;">Änderungsanträge und Kommentare beziehen sich grundsätzlich immer auf ganze
            Absätze.
        </li>
        <li style="margin-top: 7px;"><strong>Berechtigungen:</strong> Wer Anträge, Änderungsanträge und Kommentare
            verfassen darf, lässt sich jeweils festlegen. Niemand / nur Admins, Alle, oder nur Eingeloggte NutzerInnen.
        </li>
        <li style="margin-top: 7px;">Auf Wunsch: (Änderungs-)Anträge oder Kommentare erscheinen erst nach expliziter
            <strong>Freischaltung</strong> durch einen Admin.
        </li>
        <li style="margin-top: 7px;">Beliebige <strong>Textformatierungen</strong> in redaktionellen Texten (u.a. auch
            YouTube/Vimeo-Videos, Grafiken etc.). Bei Anträgen und Änderungsanträgen sind einige
            Standard-Textformatierungen möglich.
        </li>
        <li style="margin-top: 7px;">Automatisch erzeugte <strong>PDF-Versionen</strong> der Anträge und
            Änderungsanträge.
        </li>
        <li style="margin-top: 7px;">Es ist einstellbar, ob im Frontend von "Anträgen" und "Änderungsanträgen" die Rede
            ist (ausgelegt auf Parteitage), oder von "Kapiteln" und "Änderungswünschen" (ausgelegt auf die Diskussion
            von Wahlprogrammen).
        </li>
        <li style="margin-top: 7px;"><strong>E-Mail-Benachrichtigungen</strong> über neue Anträge, Änderungsanträge
            und/oder Kommentare für alle Interessierte
        </li>
        <li style="margin-top: 7px;"><strong>RSS-Feeds</strong>, damit alle Interessierte über neu eingereichte
            (Änderungs-)Anträge oder Kommentare auf dem Laufenden bleiben.
        </li>
    </ul>

    <strong>Geplant ist außerdem:</strong>
    <ul style="margin-bottom: 25px;">
        <li style="margin-top: 7px;"><strong>Veranstaltungsreihen</strong> - wenn Antragsgrün also für eine regelmäßig
            stattfindende Veranstaltung wiederholt eingesetzt werden soll (oder es mehrere Iterationen bei der
            Ausarbeitung eines Wahlprogramms geben soll), muss nicht
            jedes Mal alles aufs Neue eingerichtet werden.
        </li>
        <li style="margin-top: 7px;">AntragsstellerInnen sollen <strong>Anträge überarbeiten, Änderungsanträge
                übernehmen</strong> oder den Antrag ganz zurückziehen können.
        </li>
    </ul>

    <strong>Das kann Antragsgrün nicht</strong> (und ist auch nicht geplant):
    <ul style="margin-bottom: 15px;">
        <li style="margin-top: 7px;"><strong>Vor-Ort-Präsentationen</strong>. Auf Parteitagen selbst bietet sich der
            Einsatz von Tools an, die speziell dafür ausgelegt sind - wir empfehlen hier <a
                href="http://openslides.org/de/">OpenSlides</a>.
        </li>
        <li style="margin-top: 7px;"><strong>Wahlen / Abstimmungen</strong>.</li>
    </ul>


</div>

<?php
echo '<h2 id="selbst_nutzen" class="green">Antragsgrün selbst nutzen</h2>

<div class="content">
Um dir sofort eine eigene Version von Antragsgrün einzurichten, logge dich zunächst mit deinem
Wurzelwerk-Account ein.<br>
<br>
<strong>Erklärung / Datenschutz:</strong><br>
Du wirst, nachdem du hier deinen BenutzerInnenname eingegeben hast, auf eine "OpenID"-Seite umgeleitet, die vom
grünen Bundesverband betrieben wird (Adresse im Browser: https://service.gruene.de). Dort wirst du (ggf. auf
englisch) aufgefordert, deinen Wurzelwerk-BenutzerInnenname und -Passwort einzugeben. Diese Seite bestätigt
gegenüber Antragsgrün, dass du Parteimitglied bist und leitet deinen Namen und E-Mail-Adresse weiter - nicht
aber das Wurzelwerk-Passwort.<br>
<br>
Falls du die Zugangsdaten zurzeit nicht hast,
<a href="#wer" onclick="$(\'#wer\').next().scrollintoview(); return false;">schreib uns einfach an</a>.
<br>';

if (User::getCurrentUser()) {
    echo '<form method="GET" action="' . Html::encode(UrlHelper::createUrl('manager/createsite')) . '">
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
    echo '<div class="form-group">
        <label for="wwoauth" style="vertical-align: top; margin-top: 5px;">
            Wurzelwerk-BenutzerInnenname<br>
            <a href="https://www.netz.gruene.de/passwordForgotten.form" target="_blank" style="font-size: 0.8em;
            margin-top: -7px; display: inline-block; margin-bottom: 10px; font-weight: normal;">
                Wurzelwerk-Zugangsdaten vergessen?</a>
        </label>
        <input type="text" class="form-control" id="wwoauth" name="username" placeholder="Jane Doe">
    </div>
    <button type="submit" class="btn btn-primary" name="login_do" style="vertical-align: top;">Einloggen</button>';

    echo Html::endForm();
}
echo '</div>';

?>
<h2 id="wer green">Von wem stammt Antragsgrün?</h2>

<div class="content">
    <p>Antragsgrün wird von "<strong>Netzbegrünung</strong> - Verein für GRÜNE Netzkultur" betrieben. Programmiert wird
        es von <a href="https://www.hoessl.eu/">Tobias Hößl</a> (<a
            href="https://twitter.com/TobiasHoessl">@TobiasHoessl</a>), das Design stammt von <a
            href="http://www.netzminze.de/">Karin Wehle</a>.</p>

    <p>Erstmals eingesetzt wurde es im November 2012 vom <strong>Bayerischen Landesverband</strong> um das
        Landtagswahlprogramm zu diskutieren, später auch zur Vorbereitung der Programm-LDK.</p>

    <p>Wir werden das Antragsgrün in Zukunft weiter ausbauen und um <strong>zusätzliche Funktionen</strong> ergänzen.
        Funktionen, für die sich <strong>Sponsoren</strong> finden, werden dabei besonders priorisiert.
    </p>

    <p>Ihr könnt uns bevorzugt per <strong>E-Mail</strong> unter
        <a href="mailto:antragsgruen@netzbegruenung.de">antragsgruen@netzbegruenung.de</a>
        erreichen, in dringenden Fällen auch telefonisch unter 0151-56024223, auf <a
            href="https://twitter.com/Antragsgruen">Twitter</a>
        und auf <a href="http://www.facebook.com/Antragsgruen">Facebook</a>.</p>
</div>


<h2 id="opensource green">Open Source</h2>

<div class="content">
    <p>Wir Grüne bekennen uns schon lange zu freier Software, insofern ist es für uns selbstverständlich, dass wir
        Antragsgrün unter einer Open-Source-Lizenz zur Verfügung stellen.</p>

    <p>Der komplette Quellcode von Antragsgrün ist unter
        <a href="https://github.com/CatoTH/antragsgruen">https://github.com/CatoTH/antragsgruen</a>
        abrufbar.</p>

    <p>Antragsgrün steht unter der "GNU Affero General Public License". Das heißt, jede und jeder Interessierte kann das
        Tool nicht nur kostenlos einsetzen, sondern bei Bedarf auch Änderungen vornehmen (was auch die Verwendung in
        anderen Parteien oder völlig anderen Organisationen umfasst). Bedingung dafür ist aber unter anderem, dass wir
        als UrheberInnen weiter genannt werden und Änderungen am Tool ebenfalls wieder frei verfügbar gemacht
        werden.</p>
</div>

