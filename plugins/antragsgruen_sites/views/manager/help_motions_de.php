<?php

use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün: Mitglieder reichen Anträge ein';
/** @var \app\controllers\Base $controller */
$controller = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://antragsgruen.de/help/member-motion';
$controller->layoutParams->alternateLanuages = ['en' => 'https://motion.tools/help/member-motion'];
$controller->layoutParams->addBreadcrumb('Start', '/');
$controller->layoutParams->addBreadcrumb('Hilfe', '/help');
$controller->layoutParams->addBreadcrumb('Anträge');

?>
<h1>Mitglieder reichen Anträge ein</h1>

<div class="content managerHelpPage">

    <p><a href="/help"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Zurück zur Hilfe</a></p>
    <ul class="tocFlat">
        <li>
            <a href="#introduction" onClick="$('#introduction').scrollintoview({top_offset: -30}); return false;">
                Einleitung</a>
        </li>
        <li>
            <a href="#berechtigungen" onClick="$('#berechtigungen').scrollintoview({top_offset: -30}); return false;">
                Berechtigungen & Freischaltung</a>
        </li>
        <li>
            <a href="#antragsschluss" onClick="$('#antragsschluss').scrollintoview({top_offset: -30}); return false;">Antragsschluss</a>
        </li>
        <li>
            <a href="#kommentare" onClick="$('#kommentare').scrollintoview({top_offset: -30}); return false;">Kommentare</a>
        </li>
        <li>
            <a href="#zustimmung" onClick="$('#zustimmung').scrollintoview({top_offset: -30}); return false;">Zustimmung</a>
        </li>
        <li>
            <a href="#unterstuetzung" onClick="$('#unterstuetzung').scrollintoview({top_offset: -30}); return false;">Unterstützer*innen sammeln</a>
        </li>
    </ul>

    <h2 id="introduction">Einleitung</h2>

    <p>Es ist sehr leicht, mit Antragsgrün Mitgliedern die Möglichkeit zu geben, Anträge einzureichen - beispielsweise für eine Mitgliederversammlung oder im
        Rahmen eines Ideen-Wettbewerbs. Nachdem man sich durch den anfänglichen Einrichtungs-Dialog geklickt hat, bei dem bereits die wichtigsten Entscheidungen
        abgefragt werden, ist man im Allgemeinen bereits weitgehend fertig und kann loslegen. Es gibt aber darüber hinaus noch weitere Details, die man anpassen
        kann. Im Folgenden werden die wichtigsten davon vorgestellt.</p>

    <p>Wir gehen davon aus, dass es bereits eine eingerichtete Antragsgrün-Seite und einen Antragstypen „Anträge“ gibt (falls letzteres noch fehlt, lässt sich
        das nachholen, indem man in den Einstellungen die „Neuen Antragstyp anlegen“-Funktion nutzt und die Standard-Vorlage „Antrag“ nutzt). Anträge haben
        einen Titel, einen Antragstext und eine optionale Begründung. Falls andere Abschnitte gewünscht sind (zusätzliche Text-Abschnitte, Foto-Anhänge etc.),
        lässt sich das auch anpassen - unter [Bewerbungen] werden die Möglichkeiten hierfür genauer beschrieben.</p>

    <h2 id="berechtigungen">Berechtigungen & Freischaltung</h2>

    <p>Zwei wichtige Entscheidungen betreffen die Voraussetzungen, unter denen Mitglieder Anträge einreichen können: ist ein Login (und damit eine verifizierte
        E-Mail-Adresse) nötig, um Anträge zu stellen, oder soll dies auch ohne Login möglich sein? Und: wenn ein Mitglied einen Antrag einreicht, soll dieser
        dann automatisch für alle anderen sichtbar werden, oder soll zunächst ein Admin die Zulässigkeit des Antrags prüfen?</p>

    <p>Die Entscheidung hängt vom Anwendungsfall ab, wir können aber einige Hinweise und Erfahrungswerte geben:</p>
    <ul>
        <li>Die Antragstellung an ein (E-Mail-)Login zu koppeln stellt eine gewisse Hürde dar, da Mitglieder zunächst einmalig einen Zugang anlegen, eine
            Bestätigungs-E-Mail erhalten und diese bestätigen müssen. Bei kleineren, informelleren Veranstaltungen kann dies eine unnötige Hürde darstellen.
        </li>
        <li>Falls auf ein Login verzichtet wird, sollte allerdings auf alle Fälle eine Prüfung durch Admins erfolgen, da dann nicht verhindert werden kann, dass
            jede beliebige Person inkl. Spam-Bots Inhalte einstellen kann.
        </li>
        <li>Wenn auf ein Login verzichtet wird, können Mitglieder zwar Anträge einreichen, diese später aber nicht mehr selbst zurückziehen oder bearbeiten (nur
            noch Admins können das dann). Falls dies möglich sein soll, müssen sich Mitglieder vor dem Einreichen eines Antrags einloggen.
        </li>
    </ul>

    <p>Die Entscheidung, ob ein Login nötig ist oder nicht, wird in der Einstellung „Anträge“ -> „Berechtigungen“ -> „Antäge stellen“ getroffen:</p>
    <figure class="helpFigure center">
        <img src="/img/help/Berechtigungen.png" alt="Screenshot der Berechtigungen">
    </figure>
    <ul>
        <li>„Eingeloggte“ heißt, dass Mitglieder zunächst einen Zugang anlegen und bestätigen müssen, um Anträge anlegen zu müssen.</li>
        <li>„Alle“ heißt hingegen, dass ein Zugang nicht nötig ist. (Es ist aber dennoch möglich, sich vorher einzuloggen).</li>
        <li>„Admins“ beschränkt die Berechtigung zum Anlegen von Anträgen auf Mitglieder mit speziellen Administrator*innen-Rechten. Diese Auswahlmöglichkeit
            ist vor allem relevant, wenn Textentwürfe zur Diskussion gestellt werden, Mitglieder aber keine eigenständige Texte mehr vorschlagen können sollen -
            siehe [folgt noch, wir arbeiten daran :)].
        </li>
        <li>„Niemand“: niemand kann mehr Texte anlegen - dem Titel nach. Tatsächlich können es Admins immer noch, über den Umweg der Antragsliste. Der
            Unterschied zwischen „Admins“ und „Niemand“ ist daher marginal.
        </li>
        <li>„Ausgewählte Gruppen“: bei größeren Organisationen ist es möglich, das Recht zum Einreichen eines Antrags auf bestimmte Mitglieder einzuschränken
            (beispielsweise Delegierte), während Lesezugriff bekommen. Die Verwaltung dieser Gruppen erfolgt in den Einstellungen unter „Registrierte
            Benutzer*innen“. (Falls sowohl das Recht zum stellen und lesen von Anträgen auf eine Gruppe eingeschränkt werden soll, ist es möglich, den
            allgemeinen Zugang zur Seite unter „Diese Veranstaltung“ -> „Zugang zur Veranstaltung“ zu beschränken.)
        </li>
    </ul>
    <p>Ob Anträge vor der Veröffentlichung zunächst durch einen Admin freigeschaltet werden müssen, lässt sich über folgende Einstellung festlegen: „Diese
        Veranstaltung“ -> „Anträge“ -> „Freischaltung von Anträgen“. (Für Änderungsanträge gibt es eine separate Einstellung - zu finden unter „Diese
        Veranstaltung“ -> „Änderungsanträge“ -> „Freischaltung von Änderungsanträgen“)</p>
    <p>Wird die Freischaltung aktiviert, wird beim Einreichen eines freizuschaltenden Antrags eine E-Mail an die Admins verschickt, mit einem Link zum
        betreffenden Antrag. Welche E-Mail-Adressen das sind, lässt sich unter „Diese Veranstaltung“ -> „E-Mails“ -> „Admins“ festlegen. Sollen mehrere Admins
        benachrichtigt werden, kann man mehrere E-Mail-Adressen kommagetrennt eingeben (also z.B. „test1@example.org, test2@example.org").</p>

    <figure class="helpFigure right bordered">
        <img src="/img/help/Freischaltung.png" alt="Screenshot der Freischaltung">
    </figure>

    <p>Admins gelangen dann entweder über den Link in der E-Mail, die Antragsliste oder den dann erscheinenden „To Do“ Link im Menü zur Administrations-Seite
        des Antrags. Ganz oben erscheint dann ein markanter Button, mit dem der Antrag freigeschaltet werden kann. Beim Freischalten passiert folgendes:</p>
    <ul>
        <li>Der Status des Antrags wird von „Eingereicht (ungeprüft)“ auf „Eingereicht“ gesetzt.</li>
        <li>Ein automatisch gewähltes Antragskürzel wird gesetzt.</li>
        <li>Falls in den Einstellungen unter „Diese Veranstaltung“ -> „E-Mails“ die Option „Bestätigungs-E-Mail an die Antragsteller*in schicken“ gesetzt ist
            und die Antragstellerin eine E-Mail hinterlegt hat, bekommt er/sie eine Benachrichtigung über die Freischaltung.
        </li>
    </ul>

    <p>Hinweise hierzu:</p>
    <ul>
        <li>Will man ein anderes Antragskürzel festlegen als das automatisch Vorgeschlagene, kann man entweder statt dem Freischalten-Button den Status und das
            Kürzel händisch festlegen. Oder man kann den Button nutzen, und das Kürzel anschließend nachträglich ändern.
        </li>
        <li>Ein Kürzel muss eindeutig sein, und darf auch nicht leer sein. Die Anzeige der Kürzel gegenüber Mitgliedern lässt sich aber unterdrücken, unter
            „Aussehen und Bestandteile der Seite“ -> „Antragskürzel verstecken“.
        </li>
        <li>Man kann das Freischalten auch rückgängig machen. Hierzu ändert man einfach händisch den Status wieder zurück auf „Eingereicht (ungeprüft)“.</li>
        <li>Hat man einen Antrag geprüft, will ihn aber doch noch nicht veröffentlichen (z.B. um alle Anträge zunächst unsichtbar zu sammeln und dann zu einem
            festgelegten Zeitraum veröffentlichen), kann man den Status „Eingereicht (geprüft, unveröffentlicht)“ wählen.
        </li>
    </ul>


    <h2 id="antragsschluss">Antragsschluss</h2>

    <p>Oft gibt es ein festes Zeitfenster, innerhalb dessen Anträge gestellt werden können. Dazu lässt sich pro Antragstyp ein Antragsschluss festlegen, auf die
        Minute genau. Unter „Anträge“ -> „Antragsschluss / Zeiträume“ lässt sich dieser leicht auswählen. Ist das Feld leer, heißt das, dass es keine zeitliche
        Beschränkung gibt. Für Änderungsanträge lässt sich ein separater Zeitpunkt festlegen.</p>

    <p>Es gibt allerdings Situationen, in denen es mehrere relevante Zeitpunkte gibt. Beispielsweise könnten reguläre Anträge nur bis wenige Tage vor einer
        Mitgliederversammlung gestellt werden können, es darüber hinaus aber immer noch die Möglichkeit geben, Dringlichkeitsanträge zu stellen. Hierfür gibt es
        zwei Herangehensweisen: eine Möglichkeit ist, Dringlichkeitsanträge als absolute Ausnahme zu betrachten, die dann von den Admins eingetragen werden
        müssen (Admins können über die Antragsliste immer Anträge anlegen). Die zweite Möglichkeit ist, einen separaten Antragstypen „Dringlichkeitsanträge“
        anzulegen. Da sich ein Antragsschluss immer nur auf einen bestimmten Antragstypen bezieht, ist es darüber möglich, unterschiedliche Antragsschlüsse
        abzubilden. In diesem Fall können Mitglieder den Dringlichkeitsantrag selbst eintragen. Da die Möglichkeit zu Dringlichkeitsanträgen aber vermutlich
        weniger prominent beworben werden soll, empfiehlt es sich hier, die Option „Aufruf als großer, farbiger Button“ nicht zu aktivieren - der Link zum
        Anlegen erscheint dann etwas kleiner, dezenter auf der Startseite in der Seitenleiste.</p>

    <p>Antragsschlüsse bezeichnen immer nur das Ende des Zeitraums, in dem Mitglieder Anträge einreichen können. Falls es benötigt ist, auch einen Anfang
        festzulegen (das Einreichen soll nicht vor einem bestimmten Zeitpunkt möglich sein), gibt es hierzu zwei Möglichkeiten: entweder händisch als Admin,
        indem man bei der Berechtigung zum Anlegen zunächst „Niemand“ einstellt, und dies dann zum richtigen Zeitpunkt auf „Eingeloggte“ bzw. „Alle“ umstellt.
        Oder indem man die „Komplexe Zeitsteuerung“ aktiviert, über die man für verschiedene Aktivitäten (wie eben Anträge einzureichen) einen oder mehrere
        konkrete Zeiträume festlegen kann.<br>
        Unserer Erfahrung nach ist es hier oft zweckmäßiger, Sachen einfach zu halten.</p>

    <h2 id="kommentare">Kommentare</h2>

    <p>Anträge (und auch Änderungsanträge) können von anderen Mitgliedern kommentiert werden - falls dies von den Administrierenden so eingestellt wird. Bei der
        Ersteinrichtung der Seite wird bereits abgefragt, ob dies möglich sein soll oder nicht. Bei den Einstellungen zum Antragstypen kann dies nachträglich
        aber noch verändert bzw. genauer eingestellt werden.</p>

    <p>Die Kommentar-Funktion wird über die selbe Berechtigungs-Auswahl gesteuert wie das Einreichen von Anträgen, unter „Berechtigungen“ -> „Kommentieren“.
        Auch hier gibt es die Möglichkeit, es ganz zu deaktivieren („Niemand“), nur Mitglieder mit registrierter E-Mail-Adresse kommentieren zu lassen
        („Eingeloggte“), oder auf die Registrierung komplett zu verzichten („Alle“). (Die Auswahlmöglichkeiten „Admin“ und „Ausgewählte Gruppen“ sind bei
        Kommentaren zwar auswählbar, aber selten relevant. Genauso wie die Möglichkeit, Kommentare erst durch einen Admin freischalten zu lassen - es gibt diese
        Möglichkeit, ist aber nur in ganz wenigen Fällen relevant.)</p>

    <figure class="helpFigure right bordered">
        <img src="/img/help/Kommentare.png" alt="Screenshot eines absatzbasierten Kommentars">
    </figure>

    <p>Standardmäßig befindet sich die Kommentarfunktion unterhalb eines Antrags, Kommentare beziehen sich dabei auf den kompletten Antrag. Es ist aber auch
        möglich, Kommentare auf Absatzbasis zu ermöglichen, wenn sich Kommentare eher auf konkretere Textstellen beziehen sollen. Ist dies gewünscht, wählt man
        im Antragstyp ganz unten unter „Antrags-Abschnitte“ -> „Antragstext“ die Option „Kommentare: Pro Absatz“. Ab dann gib es für jeden Absatz eine separate
        Kommentarfunktion, die Anzahl der bereits existierenden Kommentare wird mit einem Lesezeichen rechts vom Antragstext angezeigt.</p>

    <h2 id="zustimmung">Zustimmungen</h2>

    <p>Man kann Mitgliedern die Möglichkeit geben, ihre Zustimmung zu Anträgen durch einen entsprechenden Button auszudrücken, um dadurch vor einer
        Veranstaltung schon ein ungefähres Stimmungsbild zu erhalten. Diese Funktion ist standardmäßig nicht aktiviert, kann aber für einen bestimmten
        Antragstypen folgendermaßen aktiviert werden:</p>
    <ul>
        <li>Unter „Berechtigungen“ -> „Anträge unterstützen“ zunächst auswählen, wem diese Funktion offen stehen soll.</li>
        <li>Dann direkt darunter das Häkchen bei „Zustimmung“ setzen.</li>
        <li>Soll es neben einer Zustimmungs-Funktion auch eine Möglichkeit geben, die Ablehnung explizit auszudrücken, kann man außerdem das Häkchen bei
            „Ablehnung“ setzen.
        </li>
        <li>Das dritte Häkchen, „Offiziell unterstützen“, sollte hier nicht gesetzt werden - dieses ist in einem anderen Zusammenhang relevant (siehe
            „Unterstützer*innen sammeln“).
        </li>
    </ul>

    <h2 id="unterstuetzung">Unterstützer*innen sammeln</h2>

    <p>Größere Veranstaltungen mit vielen Antragsberechtigten haben oft strengere Bestimmungen, unter denen Anträge eingereicht werden können - insbesondere
        ist es oft nötig, dass mehrere Mitglieder einen Antrag gemeinsam stellen bzw. unterstützen müssen, um diesen einzureichen.</p>

    <p>Hier unterstützt Antragsgrün grob gesagt zwei Herangehensweisen: entweder gibt die Haupt-Antragsteller*in selbst alle Personen an, die den Antrag
        unterstützen (Vertrauensbasis), oder aber die Haupt-Antragsteller*in legt den Antrag zunächst an, kann den Antrag aber erst dann final einreichen, wenn
        genügend Unterstützer*innen auch explizit ihre Unterstützung ausgedrückt haben (sicherer, aber aufwendiger für alle Beteiligten).</p>

    <p><strong>Einfacherer Fall - die Antragsteller*in gibt die weiteren Personen an:</strong></p>
    <p>Um diese Variante zu wählen, wählt man beim Antragstypen unter „Antragsteller*in / Unterstützer*innen: Anträge“ zunächst das Formular „Von der
        Antragsteller*in angegeben“ aus. Es erscheint daraufhin ein neues Feld, „Unterstützer*innen“. Hier kann angegeben werden, wie viele Personen angegeben
        werden müssen, und auch ob die Antragsteller*in darüber hinaus noch weitere Personen angeben kann.</p>

    <p><strong>Komplexerer Fall: eine explizite Unterstützungs-Sammel-Phase</strong></p>
    <p>Soll die sicherere (aber auch aufwändigere) Variante zum Einsatz kommen, dass jede beteiligte Person auch tatsächlich einmal die Seite aufrufen und dort
        explizit ihre Unterstützung ausdrücken muss, sind ein paar mehr Einstellungen vorzunehmen:</p>
    <ul>
        <li>Beim Unterstützungs-Formular ist die Variante „Unterstützungs-Phase vor Veröffentlichung (außer bei Gremien)“ zu wählen</li>
        <li>Die Mindestzahl an Unterstützer*innen einige Felder muss ausgefüllt werden</li>
        <li>Weiter oben, unter „Berechtigungen“ -> „Anträge Unterstützen“, sollte mindestens „Eingeloggte“ ausgewählt werden (andere Möglichkeiten sind auch
            möglich, zweckmäßig ist es aber, mindestens ein Login anzufordern)
        </li>
        <li>Unter „Anträge Unterstützen“ ist das Häkchen bei „Offiziell unterstützen“ zu setzen.</li>
        <li>Empfehlenswert ist es außerdem, die E-Mail-Bestätigung beim Einreichen für Mitglieder zu aktivieren, unter „Diese Veranstaltung“ -> „E-Mail“
            unten.
        </li>
    </ul>

    <figure class="helpFigure right bordered">
        <img src="/img/help/Unterstuetzung.png" alt="Screenshot eines absatzbasierten Kommentars">
    </figure>
    <p>Will ein Mitglied nun einen Antrag einreichen, kommt nun im Anschluss ein Bestätigungsbildschirm, in dem das Mitglied den Link auf den Antrag angezeigt
        bekommt. Dieser Link kann nun an weitere Personen weitergeleitet werden. Öffnen diese den Link, bekommen sie neben dem Antrag die Möglichkeit, den
        Antrag zu unterstützen. Haben dies ausreichend viele Mitglieder getan, erhält die ursprüngliche Antragsteller*in eine E-Mail-Benachrichtigung, dass der
        Antrag nun tatsächlich eingereicht werden kann / soll.</p>
</div>
