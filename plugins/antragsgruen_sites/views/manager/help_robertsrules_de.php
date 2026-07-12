<?php

use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün: Robert’s Rules of Order';
/** @var \app\controllers\Base $controller */
$controller = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://antragsgruen.de/help/roberts-rules';
$controller->layoutParams->alternateLanuages = ['en' => 'https://motion.tools/help/roberts-rules'];
$controller->layoutParams->addBreadcrumb('Start', '/');
$controller->layoutParams->addBreadcrumb('Hilfe', '/help');
$controller->layoutParams->addBreadcrumb('Robert’s Rules of Order');

?>
<h1>Antragsgrün mit Robert’s Rules of Order verwenden</h1>

<div class="content managerHelpPage">

    <p><a href="/help"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Zurück zur Hilfe</a></p>

    <h2>Vorwort</h2>

    <p>Robert’s Rules of Order (RRO) ist das vor allem im Englischsprachigen Raum verbreitete Regelwerk für die Durchführung von Versammlungen von Vereinen, Verbänden und Gremien. Im Deutschsprachigen Raum kommen zwar weniger standardisierte Verfahren zum Einsatz, das meiste im Folgenden Beschriebene lässt sich aber dennoch gut anwenden.</p>

    <p><em>Hinweis: Einige der hier beschriebenen Funktionen (der Bereich „Aktuell debattiert“ sowie aus der Versammlung heraus gestellte Verfahrensanträge) sind Teil eines neuen Moduls, das sich derzeit in Entwicklung befindet und für Antragsgrün 4.18 geplant ist.</em></p>

    <h2>Wie die Konzepte von Robert’s Rules auf Antragsgrün abbilden</h2>

    <table class="statusReferenceTable">
        <colgroup>
            <col class="name">
            <col>
        </colgroup>
        <thead>
        <tr>
            <th>Robert’s Rules of Order</th>
            <th>Antragsgrün</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Session / Versammlung</td>
            <td>Eine <strong>Veranstaltung</strong> (Consultation). Jede Versammlung oder Gremiensitzung erhält ihre eigene Veranstaltung; eine Antragsgrün-Seite kann viele davon beherbergen, z.B. eine pro Jahr oder pro Gremium.</td>
        </tr>
        <tr>
            <td>Agenda / Tagesordnung</td>
            <td>Die <strong>Tagesordnung</strong> auf der Startseite der Veranstaltung. Anträge können Tagesordnungspunkten zugeordnet werden; allgemeine Punkte („Begrüßung“, Berichte, Wahlen) sind einfach Tagesordnungspunkte ohne Anträge.</td>
        </tr>
        <tr>
            <td>Main motion / Hauptantrag</td>
            <td>Ein <strong>Antrag</strong>. Antragstypen legen fest, wer Anträge stellen darf (alle Mitglieder, Eingeloggte, Admins, bestimmte Benutzergruppen) und ob sie vorab oder während der Versammlung eingereicht werden.</td>
        </tr>
        <tr>
            <td>Subsidiary, privileged und incidental motions<br>(Point of Order, Vertagung, Verweisung an einen Ausschuss, Schluss der Debatte, Sitzungsunterbrechung, &hellip;)</td>
            <td><strong>Verfahrensanträge</strong>: reguläre Anträge eigener Antragstypen, die auf der Startseite nicht gelistet werden. Sie werden über den Bereich „Aktuell debattiert“ mit einem vereinfachten Formular aus der Versammlung heraus gestellt; ob sie eine Unterstützung benötigen und ob sie einen Text haben, wird pro Antragstyp konfiguriert. Die Art („Point of Order“, „Antrag auf Unterbrechung“, &hellip;) ist ein auswählbares Label. (<em>Ab Version 4.18</em>)</td>
        </tr>
        <tr>
            <td>Amendment / secondary amendment</td>
            <td><strong>Änderungsanträge</strong> sind das Herzstück von Antragsgrün. Sie können pro Antragstyp aktiviert werden. Antragsgrün zeigt die vorgeschlagene Änderung als Gegenüberstellung zum Originaltext an.</td>
        </tr>
        <tr>
            <td>Second (Unterstützung eines Antrags)</td>
            <td>Eine <strong>Unterstützung</strong>. Wird der Antragstyp so konfiguriert, dass vor der Veröffentlichung Unterstützer*innen gesammelt werden (Minimum: eine Person), geht ein gestellter Antrag erst weiter, wenn ihn ein weiteres Mitglied unterstützt hat. Mit automatischer Einreichung ist nach der Unterstützung keine weitere Aktion der antragstellenden Person nötig.</td>
        </tr>
        <tr>
            <td>Obtaining the floor / Worterteilung</td>
            <td><strong>Redelisten</strong>. Mitglieder melden sich online zu Wort; die Versammlungsleitung erteilt das Wort, indem sie den nächsten Redeslot startet. Redelisten können an den gerade debattierten Antrag, Änderungsantrag oder Tagesordnungspunkt geknüpft werden.</td>
        </tr>
        <tr>
            <td>Rede für / gegen einen Antrag</td>
            <td><strong>Quotierte Redelisten</strong>, z.B. „Dafür“ und „Dagegen“, sodass die Versammlungsleitung wie von Robert’s Rules empfohlen zwischen beiden Seiten abwechseln kann. Auch andere Quotierungen (z.B. nach Geschlecht) sind möglich.</td>
        </tr>
        <tr>
            <td>Redezeitbegrenzung</td>
            <td>Die <strong>Redezeit</strong>-Einstellung einer Redeliste, inklusive sichtbarem Countdown.</td>
        </tr>
        <tr>
            <td>Voting / Abstimmung</td>
            <td>Eine <strong>Abstimmung</strong>. Ja/Nein/Enthaltung über Anträge, Änderungsanträge oder freie Fragen, mit konfigurierbarer Mehrheit (einfache Mehrheit, Zweidrittelmehrheit, &hellip;), konfigurierbarer Stimmberechtigung (Benutzergruppen) und konfigurierbarer Sichtbarkeit des Stimmverhaltens (geheime Abstimmung vs. namentliche Abstimmung).</td>
        </tr>
        <tr>
            <td>Quorum</td>
            <td>Die <strong>Quorums</strong>-Einstellungen einer Abstimmung, bezogen auf die Zahl der stimmberechtigten bzw. anwesenden Mitglieder.</td>
        </tr>
        <tr>
            <td>Chair / Versammlungsleitung</td>
            <td>Nutzer*innen mit dem Recht zur <strong>Debattenmoderation</strong> (oder Veranstaltungs-Admins). Dieses Recht kann über eine Benutzergruppe vergeben werden, sodass die Versammlungsleitung keine vollen Administrationsrechte benötigt.</td>
        </tr>
        </tbody>
    </table>

    <h2>Vor der Versammlung</h2>

    <ul>
        <li><strong>Veranstaltung anlegen</strong> und die <strong>Tagesordnung</strong> entsprechend der Order of Business einrichten.</li>
        <li><strong>Antragstyp für Hauptanträge anlegen.</strong> Konfigurieren, wer Anträge stellen darf; falls Anträge eine Unterstützung benötigen, die Unterstützungsphase mit einem Minimum von einer Person und automatischer Einreichung aktivieren.</li>
        <li><strong>Antragstypen für Verfahrensanträge anlegen</strong>, die aus der Versammlung heraus möglich sein sollen - z.B. einen Typ „Point of Order“ (ohne Unterstützung, ohne Text) und einen Typ „Verfahrensantrag“ (mit Unterstützung, optionalem Text) für Vertagung, Unterbrechung, Schluss der Debatte usw. Diese Typen werden auf der Startseite nicht gelistet.</li>
        <li><strong>Den Bereich „Aktuell debattiert“ aktivieren</strong> (in den Darstellungs-Einstellungen der Veranstaltung) und die quotierten Redelisten („Dafür“ / „Dagegen“) konfigurieren.</li>
        <li><strong>Der Versammlungsleitung das Debattenmoderations-Recht geben</strong> (über eine Benutzergruppe) und die Benutzergruppen der stimmberechtigten Mitglieder einrichten, damit Abstimmungen darauf beschränkt werden können.</li>
    </ul>

    <h2>Während der Versammlung</h2>

    <ul>
        <li>Die Versammlungsleitung <strong>wählt den aktuellen Beratungsgegenstand aus</strong> - einen Antrag, einen Änderungsantrag oder einen Tagesordnungspunkt. Er wird sofort allen Teilnehmenden im Bereich „Aktuell debattiert“ auf der Startseite angezeigt.</li>
        <li>Mitglieder <strong>melden sich zu Wort</strong>, in der Liste „Dafür“ oder „Dagegen“; die Versammlungsleitung arbeitet die Redeliste ab und wechselt zwischen den Seiten.</li>
        <li>Mitglieder können <strong>Verfahrensanträge stellen</strong> (z.B. einen Point of Order oder Schluss der Debatte), direkt aus dem Widget heraus. Benötigt die Art eine Unterstützung, wird der Antrag anhängig, sobald ihn ein weiteres Mitglied unterstützt - die Versammlungsleitung wird deutlich darauf hingewiesen.</li>
        <li>Die Versammlungsleitung kann die Debatte <strong>auf einen anhängigen Verfahrensantrag umschalten</strong>, ihm eine Redeliste zuordnen oder mit wenigen Klicks eine <strong>Abstimmung starten</strong> - und danach zum unterbrochenen Hauptantrag zurückkehren.</li>
        <li>Ist die Debatte geschlossen, <strong>stellt die Versammlungsleitung die Frage</strong>: Abstimmung über die Änderungsanträge, dann über den Hauptantrag. Das Ergebnis bestimmt den Status (angenommen / abgelehnt); angenommene Texte können als Beschlüsse veröffentlicht werden.</li>
    </ul>

    <h2>Was Antragsgrün nicht übernimmt</h2>

    <p>Einige Aspekte von RRO werden aktuell nicht unterstützt, teils weil bislang kein Bedarf für technische Unterstützung gab, teils weil schlicht noch nicht umgesetzt wurde. Dazu gehören:</p>
    <ul>
        <li>Protokollführung</li>
        <li>Erzwingen einer bestimmter Reihenfolge von Verfahrensvorschlägen</li>
        <li>Personenwahlen</li>
    </ul>
</div>
