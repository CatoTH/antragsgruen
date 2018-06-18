<?php
use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 * @var \app\controllers\Base $controller
 */

$this->title = 'Antragsgrün - die grüne Online-Antragsverwaltung';
$controller  = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl      = 'https://antragsgruen.de/help';
$controller->layoutParams->alternateLanuages = ['en' => 'https://motion.tools/help'];

/** @var \app\models\settings\AntragsgruenApp $params */
$params = \Yii::$app->params;

?>
<h1>Antragsgrün - das grüne Antragstool</h1>

<div class="content managerHelpPage">

    <h2>Antragsgrün - Funktionsübersicht und Handbuch</h2>

    <ul class="toc">
        <li>
            <a href="#grundlegender_aufbau"
               onClick="$('#grundlegender_aufbau').scrollintoview({top_offset: -30}); return false;">Grundlegender
                Aufbau einer Antragsgrün-Seite</a>
            <ul>
                <li><a href="#antraege" onClick="$('#antraege').scrollintoview({top_offset: -30}); return false;">Anträge
                        / Änderungsanträge</a></li>
                <li><a href="#veranstaltungen"
                       onClick="$('#veranstaltungen').scrollintoview({top_offset: -30}); return false;">Veranstaltungen</a>
                </li>
                <li><a href="#antragstypen"
                       onClick="$('#antragstypen').scrollintoview({top_offset: -30}); return false;">Antragstypen /
                        Motion Type</a></li>
                <li><a href="#tagesordnung"
                       onClick="$('#tagesordnung').scrollintoview({top_offset: -30}); return false;">Tagesordnungspunkte</a>
                </li>
            </ul>
        </li>
        <li>
            <a href="#arbeitsablauf" onClick="$('#arbeitsablauf').scrollintoview({top_offset: -30}); return false;">Arbeitsabläufe:
                Antragstellung, Freischaltung, Berechtigungen</a>
            <ul>
                <li><a href="#antragsteller_innen"
                       onClick="$('#antragsteller_innen').scrollintoview({top_offset: -30}); return false;">Antragsteller*innen
                        / Unterstützer*innen</a></li>
                <li><a href="#freischaltung"
                       onClick="$('#freischaltung').scrollintoview({top_offset: -30}); return false;">Freischaltung von
                        Anträgen</a></li>
                <li><a href="#login" onClick="$('#login').scrollintoview({top_offset: -30}); return false;">Login /
                        Berechtigungen</a></li>
                <li><a href="#antragsschluss"
                       onClick="$('#antragsschluss').scrollintoview({top_offset: -30}); return false;">Antragsschluss</a>
                </li>
                <li><a href="#benachrichtigungen"
                       onClick="$('#benachrichtigungen').scrollintoview({top_offset: -30}); return false;">Benachrichtigungen</a>
                </li>
            </ul>
        </li>
        <li>
            <a href="#aes_uebernehmen" onClick="$('#aes_uebernehmen').scrollintoview({top_offset: -30}); return false;">Änderungsanträge
                übernehmen / einpflegen</a>
            <ul>
                <li><a href="#einzelne_uebernehmen"
                       onClick="$('#einzelne_uebernehmen').scrollintoview({top_offset: -30}); return false;">Einzelne
                        Änderungsanträge einpflegen</a></li>
                <li><a href="#alle_uebernehmen"
                       onClick="$('#alle_uebernehmen').scrollintoview({top_offset: -30}); return false;">Alle
                        Änderungsanträge einpflegen / (Öffentliche) Zwischenstände</a></li>
            </ul>
        </li>
        <li>
            <a href="#export"
               onClick="$('#export').scrollintoview({top_offset: -30}); return false;">Export-Funktionen</a>
            <ul>
                <li><a href="#export_pdf" onClick="$('#export_pdf').scrollintoview({top_offset: -30}); return false;">PDF</a>
                </li>
                <li><a href="#export_opendocument"
                       onClick="$('#export_opendocument').scrollintoview({top_offset: -30}); return false;">OpenDocument
                        / Textverarbeitungsdokument</a></li>
                <li><a href="#export_tabelle"
                       onClick="$('#export_tabelle').scrollintoview({top_offset: -30}); return false;">Änderungsanträge
                        in Tabellenform</a></li>
                <li><a href="#export_openslides"
                       onClick="$('#export_openslides').scrollintoview({top_offset: -30}); return false;">Openslides /
                        CSV</a></li>
                <li><a href="#export_weitere"
                       onClick="$('#export_weitere').scrollintoview({top_offset: -30}); return false;">HTML, Plain Text,
                        RSS, Weitere Formate</a></li>
            </ul>
        </li>
        <li>
            <a href="#weitere_funktionen"
               onClick="$('#weitere_funktionen').scrollintoview({top_offset: -30}); return false;">Weitere
                Funktionen</a>
            <ul>
                <li><a href="#layout" onClick="$('#layout').scrollintoview({top_offset: -30}); return false;">Layout-Anpassbarkeit</a>
                </li>
                <li><a href="#zeilennummerierung"
                       onClick="$('#zeilennummerierung').scrollintoview({top_offset: -30}); return false;">Zeilennummerierung</a>
                </li>
                <li><a href="#redaktionelle_aes"
                       onClick="$('#redaktionelle_aes').scrollintoview({top_offset: -30}); return false;">Redaktionelle
                        Änderungsanträge</a></li>
                <li><a href="#antragskuerzel"
                       onClick="$('#antragskuerzel').scrollintoview({top_offset: -30}); return false;">Antragskürzel</a>
                </li>
                <li><a href="#themen" onClick="$('#themen').scrollintoview({top_offset: -30}); return false;">Themen /
                        Tags</a></li>
                <li><a href="#kommentare" onClick="$('#kommentare').scrollintoview({top_offset: -30}); return false;">Kommentare</a>
                </li>
                <li><a href="#zustimmung" onClick="$('#zustimmung').scrollintoview({top_offset: -30}); return false;">Zustimmung
                        / Ablehnung</a></li>
                <li><a href="#uebersetzen" onClick="$('#uebersetzen').scrollintoview({top_offset: -30}); return false;">Übersetzung
                        der Seite / Anpassungen des Wordings</a></li>
            </ul>
        </li>
    </ul>


    <h2 id="grundlegender_aufbau">Grundlegender Aufbau einer Antragsgrün-Seite</h2>
    <h3 id="antraege">Anträge / Änderungsanträge</h3>
    <p>Als Anträge werden alle eigenständige Dokumente bezeichnet, die auf Antragsgrün eingestellt werden können.
        Anträge für Parteitage, Mitgliederversammlungen usw. sind einer der häufigsten Einsatzgebiete für Antragsgrün,
        daher verwenden wir den Begriff stellvertretend für alle Dokumenttypen. Tatsächlich können auch Wahlprogramme,
        Textentwürfe, Bewerbungen einschließlich Fotos, Satzungen usw. verwaltet werden.</p>
    <p>Änderungsanträge beziehen sich unmittelbar auf einen Antrag (bzw. andere eingestellte Texte) und haben den Zweck,
        diesen zu verändern. Antragsgrün will vor allem die Handhabung vieler Änderungsanträge pro Antrag vereinfachen
        und formalisiert diese daher stark: beim Einreichen eines Änderungsantrags muss explizit angegeben werden, die
        der Antrag nach der beantragten Änderung aussehen soll. Antragsgrün kann so automatisch die tatsächlichen
        Änderungen feststellen, den ursprünglichen Antrag so annotieren, dass leicht ersichtlich ist, zu welchen
        Passagen es Alternativvorschläge gibt, und auf Wunsch die Änderungen in den ursprünglichen Antrag
        übernehmen.</p>
    <p>Sowohl beim Einreichen von Anträgen als auch bei Änderungsanträgen können zusätzliche Informationen über die
        einreichende Person bzw. die einreichende Personengruppe abgefragt werden, um sich den organisatorischen
        Vorgaben der jeweiligen Organisation anzupassen.</p>

    <h3 id="veranstaltungen">Veranstaltungen</h3>
    <p>Eine „Veranstaltung“ - manchmal auch „Programmdiskussion“ bezeichnet, ist die Sammlung aller Anträge, die
        zeitgleich diskutiert werden können. Das kann eine konkrete Verbandstagung, der Entwurf eines Wahlprogramms (in
        dem die einzelnen Kapitel jeweils eigene „Anträge“ sind) oder eine Wahl sein, für die Bewerbungen gesammelt
        werden.</p>
    <p>Jede Antragsgrün-Seite hat mindestens eine solche Veranstaltung, kann aber beliebig viele haben: wenn also eine
        Veranstaltung wiederholt stattfindet, muss nicht für jede eine neue Antragsgrün-Seite eingerichtet werden oder
        erst die alten Anträge gelöscht werden. Links auf frühere Anträge und Veranstaltungen bleiben für Archivzwecke
        erhalten, die Startseite zeigt aber jeweils die aktuellste Veranstaltung ein.</p>
    <p>Neue Veranstaltungen innerhalb der selben Seite können unter „Einstellungen“ unter „Weitere Veranstaltungen
        anlegen / verwalten“ eingerichtet werden, hier kann auch festgelegt werden, welche standardmäßig angezeigt
        werden soll.</p>
    <!-- @TODO: Verzeichnis -->

    <h3 id="antragstypen">Antragstypen / Motion Type</h3>
    <p>Innerhalb einer Veranstaltung kann es verschieden strukturierte Dokumente geben – oder Dokumente, die
        unterschiedlichen Regelungen unterliegen, z.B. was Antragsfristen oder Berechtigungen zum Anlegen angeht.
        Beispielsweise benötigen Bewerbungen andere Angaben (Name, Selbstvorstellung, Foto, weitere Angaben, …) als
        Anträge (Titel, Antragstext, Begründung) und bieten üblicherweise keine Möglichkeit, Änderungsanträge zu
        stellen. Für manche Veranstaltungen soll es die Möglichkeit für Dringlichkeitsanträge geben, die gesondert von
        regulären Anträgen mit späterem Antragsschluss eingereicht werden können.</p>
    <p>Um diese Flexibilität zu ermöglichen, unterstützt jede Veranstaltung auf Antragsgrün beliebig viele Antragstypen.
        Jeder Antragstyp hat einen eigenen Namen, Struktur, Berechtigungen usw. und jeder eingereichte Antrag gehört zu
        genau einem Antragstypen.</p>
    <p>Die verschiedenen Antragstypen können direkt unter „Einstellungen“ bei „Antragstypen bearbeiten“ verwaltet
        werden.</p>

    <h3 id="tagesordnung">Tagesordnungspunkte</h3>
    <p>Tagesordnungen sind eine Funktion von Antragsgrün, die auf Wunsch aktiviert werden kann, aber nicht obligatorisch
        ist.</p>
    <p>Wenn eine Tagesordnung verwendet wird, werden auch alle Anträge jeweils einem Tagesordnungspunkt zugeordnet.
        Daher arbeitet die Tagesordnung eng mit den Antragstypen zusammen: für jeden Tagesordnungspunkt kann (muss aber
        nicht) bei der Einrichtung der Seite ein Antragstyp ausgewählt werden. Damit können dann für diese
        Tagesordnungspunkte Anträge von genau diesem Typ eingereicht werden, die dann anschließend hier erscheinen. Für
        eine Vorstandswahl könnte es beispielsweise für die Wahl eines jeden Vorstandsposten einen Tagesordnungspunkt
        geben, jeder mit dem Antragstyp „Bewerbung“; so können dann Bewerbungen gezielt für einen bestimmten Posten
        eingereicht werden.</p>
    <p>Um Tagesordnungen zu aktivieren: Erst unter „Einstellungen“ in der Unterseite „Diese Veranstaltung“ bei
        „Startseiten-Design“ einen der beiden Tagesordnungspunkt-Punkte auswählen. Dann kann man auf der Startseite neue
        Tagesordnungspunkte anlegen.</p>
    <!-- @TODO: Genauere Erklärung zur Nummerierung von Tagesordnungspunkten -->

    <h2 id="arbeitsablauf">Arbeitsabläufe: Antragstellung, Freischaltung, Berechtigungen</h2>

    <h3 id="antragsteller_innen">Antragsteller*innen / Unterstützer*innen</h3>
    <p>Je nach Organisation und Veranstaltung gibt es unterschiedliche Voraussetzungen, die erfüllt sein müssen, um
        einen (Änderungs-)Antrag zu stellen. Antragsgrün versucht, die wichtigsten Fälle abzudecken:</p>
    <ul>
        <li>Im einfachsten Fall kann einfach von jeder und jedem ein Antrag eingereicht werden, evtl. gekoppelt an ein
            Login.
        </li>
        <li>In einigen Organisationen ist es nötig, für einen Antrag auch noch eine bestimmte Zahl an unterstützender
            Personen zu benennen, die den Antrag mit tragen. Die Namen (und ggf. Organisationsgliederungen) der
            unterstützenden Personen werden von der Antragsteller*in hier mit angegeben. Falls der Antrag von einem
            Gremium gestellt wird, entfällt diese Angabe.
        </li>
        <li>Wenn technisch sichergestellt werden muss, dass im vorigen Fall auch jede unterstützende Person auch
            tatsächlich hinter dem Antrag steht, ist es möglich, dem Einreichen eines Antrags eine explizite
            „Unterstützung sammeln“-Phase voranzustellen. Dann kann der Antrag zunächst angelegt werden, gilt aber noch
            nicht offiziell als eingereicht: erst einmal haben andere Personen die Möglichkeit, sich auf die
            Unterstützungsliste zu stellen. Sobald die Mindestzahl an Unterstützer*innen zusammengekommen ist, kann der
            Antrag tatsächlich eingereicht werden. Wegen dem vergleichsweise hohen Aufwand durch dieses Vorgehen kommt
            dies im Allgemeinen nur in sehr großen Veranstaltungen zum Einsatz.
        </li>
    </ul>
    <p>Pro Antragstyp kann separat eine der genannten Varianten vergeben werden. Die Auswahl dafür findet sich unter
        „Einstellungen“ → Antragstypen bearbeiten → Antragsteller*in / Unterstützer*innen.</p>
    <p>Für die dritte Variante mit einer vorgeschalteten Unterstützungsphase muss bei „(Änderung-)Anträge unterstützen“
        außerdem die Berechtigung auf „Eingeloggte“ beschränkt werden und die Möglichkeit „Offiziell unterstützen“
        aktiviert werden.</p>
    <p>Wenn Sie weitere Möglichkeiten benötigen, kontaktieren Sie uns.</p>

    <h3 id="freischaltung">Freischaltung von Anträgen</h3>
    <p>Es ist oft gewünscht, eingereichte Anträge vor der Veröffentlichung noch redaktionell zu prüfen, z.B. durch eine
        Programm- oder Antragskommission. In Fällen, in denen das Einreichen eines Antrags ohne Login zugelassen wird,
        ist das sehr empfehlenswert, um eventuelle Einträge durch Spam-Bots abzufangen. Drei Möglichkeiten gibt es, die
        für alle Anträge gemeinsam, und für alle Änderungsanträge gemeinsam gewählt werden können:</p>
    <ul>
        <li>Keine Freischaltung: Anträge sind nach der Einreichung sofort sichtbar</li>
        <li>Freischaltung: Anträge müssen zuerst durch einen Admin geprüft werden, bevor sie sichtbar sind</li>
        <li>Eine Mischung aus beiden: Anträge können sofort sichtbar sein, müssen aber dennoch durch einen Admin geprüft
            werden. Bis dahin werden sie angezeigt, allerdings ausgegraut und als noch nicht geprüft markiert.
        </li>
    </ul>
    <p>Die Einstellung hierfür findet sich unter „Einstellungen“ → „Diese Veranstaltung“. Relevant sind die Punkte
        „Freischaltung von Anträgen“, „Freischaltung von Änderungsanträgen“ und „Anträge (ausgegraut) anzeigen, auch
        wenn sie noch nicht freigeschaltet sind“.</p>

    <h3 id="login">Login / Berechtigungen</h3>
    <p>Funktionen wie das Stellen von Anträgen, das Kommentieren oder Unterstützen von Anträgen können auf eingeloggte
        Mitglieder beschränkt werden. Antragsgrün unterstützt dabei grundsätzlich verschiedene verschiedene
        Login-Mechanismen.</p>
    <p>Standardmäßig ist es möglich, sich mit seiner E-Mail-Adresse bei einer Antragsgrün-Version zu registrieren und
        damit die entsprechenden Funktionen zu nutzen. Für geschlossene Benutzerkreise ist es möglich, das Login auf
        bekannte Mitglieder zu beschränken, also das selbstständige Registrieren mit einer neuen E-Mail-Adresse zu
        deaktivieren und stattdessen Einladungen an eine Liste an E-Mail-Adressen zu schicken. Diese Funktion findet
        sich unter „Einstellungen“ im Punkt „Login / Benutzer*innen / Admins“. Es muss der Punkt „Nur ausgewählten
        Benutzer*innen das Login erlauben“ aktiviert werden. Anschließend erscheint weiter unten der Punkt
        „Benutzer*innen-Accounts“, in dem neue Mitglieder eingeladen werden können.</p>
    <p>Wenn Antragsgrün in Organisationen verwendet werden soll, die eine Single-Sign-On-Lösung verwenden, lässt sich
        die Authentifizierung auf weitere Mechanismen ausweiten; produktiv kamen Bereits OpenID- sowie SAML-Konnektoren
        zum Einsatz. Für weitere Informationen schreiben Sie uns bitte einfach an.</p>

    <h3 id="antragsschluss">Antragsschluss</h3>
    <p>Wenn das Einreichen von Anträgen oder Änderungsanträgen nur bis zu einem bestimmten Zeitpunkt möglich sein soll,
        gibt es die Möglichkeit, jeweils einen Antragsschluss zu definieren. Pro Antragstyp sind eigene Fristen
        möglich.</p>
    <p>Die Einstellung hierfür sind zu finden unter: „Einstellungen“ → Antragstyp bearbeiten → Antragsschluss.</p>

    <h3 id="benachrichtigungen">Benachrichtigungen</h3>
    <p>Antragsgrün bietet eine Reihe an Funktionen, Interessierte über relevante Ereignisse zu informieren. Alle
        Benachrichtigungen werden dabei per E-Mail versandt.</p>
    <p>Für Teilnehmer*innen sind die meisten Benachrichtigungen optional. Nachdem man sich mit seiner E-Mail-Adresse
        registriert hat, kann man auf der Startseite in der Sidebar den Punkt „E-Mail-Benachrichtigungen“ auswählen.
        Hier lässt sich jeweils einzeln auswählen, ob man über neue Anträge, neue Änderungsanträge und neue Kommentare
        benachrichtigt werden soll. Standardmäßig wird man über neu gestellte Änderungsanträge zu Anträgen
        benachrichtigt, die man selbst eingelegt hat. Außerdem werden Antragsteller*innen benachrichtigt, wenn ein
        eingereichter Antrag geprüft und damit dann öffentlich verfügbar ist.</p>
    <p>Für Administrator*innen ist besonders relevant, wenn neue (Änderung-)Anträge eingereicht werden, die geprüft
        werden müssen. Darüber hinaus gibt es Benachrichtigungen, wenn bereits eingereichte Anträge zurückgezogen oder
        nachträglich bearbeitet werden.</p>
    <p>Jenseits von E-Mail-Benachrichtigungen werden auch mehrere RSS-Feeds unterstützt, um darüber über die neuesten
        Anträge, Änderungsanträge und Kommentare einer Veranstaltung informiert zu bleiben. Die Feeds befinden sich auf
        der Startseite in der Sidebar rechts.</p>

    <h2 id="aes_uebernehmen">Änderungsanträge übernehmen / einpflegen</h2>
    <p>Antragsgrün bietet mehrere Möglichkeiten, wie die in Änderungsanträgen vorgeschlagenen Änderungen in Anträge
        übernommen werden können. Es kann entweder ein einzelner Änderungsantrag übernommen werden, während andere
        Änderungsanträge zum Antrag aufrecht erhalten bleiben, oder es kann aus allen Änderungsanträgen eine gemeinsame
        neue Fassung des Antrags erstellt werden.</p>
    <p>Wichtig als Vorbemerkung hierzu ist nur zu wissen: wenn es zwei oder mehr Änderungsanträge gibt, die Änderungen
        an der selben Textstelle vornehmen wollen, kommt es hier zu Konflikten zwischen diesen Änderungsanträgen, die
        händisch aufgelöst werden müssen – was oft nicht ganz trivial ist.</p>
    <p>Das grundsätzliche Prinzip bei beiden Varianten ist dabei: durch die Übernahme des Änderungsantrags bzw. der
        Änderungsanträge entsteht eine neue Version des Antrags mit dem neuen Text. Die alte Version bleibt zusammen mit
        den Änderungsanträgen erhalten, sodass transparent bleibt, was sich an einem Antrag geändert hat. Wenn nur ein
        einzelner Änderungsantrag übernommen wird, verbleiben diejenigen anderen Änderungsanträge bei der alten Version,
        die in diesem Zuge als abgelehnt oder (Modifiziert) Angenommen markiert wurden, während diejenigen, die auf
        „Unverändert: Eingereicht“ verbleiben mit in die neue Version des Antrags übernommen werden.</p>

    <h3 id="einzelne_uebernehmen">Einzelne Änderungsanträge einpflegen</h3>
    <p>Die Möglichkeit, einen einzelnen Änderungsantrag zu übernehmen, befindet sich in der normalen Ansicht des
        Änderungsantrags in der rechten Sidebar unter „In den Antrag übernehmen“.</p>
    <p>Der Vorgang besteht aus mehreren Schritten. Im ersten Schritt kann das Kürzel der neuen Antragsversion vergeben
        werden, die durch diese Übernahme entsteht. Außerdem kann auch angegeben werden, ob sich durch diese Übernahme
        eventuell andere Änderungsanträge erübrigen. Das ist insbesondere deshalb relevant, da mit Änderungsanträgen,
        die sich dadurch erübrigen, im weiteren Verlauf keine Konflikte mehr entstehen können.</p>
    <p>Im nächsten Schritt kann angegeben werden, ob der Änderungsantrag eins zu eins so wie gestellt übernommen wird,
        oder ob es eine modifizierte Übernahme sein soll. In letzterem Fall kann man die betroffenen Absätze noch einmal
        nachbearbeiten.</p>
    <p>Im letzten Schritt wird nun überprüft, ob sich die Änderungen möglicherweise mit Änderungsanträgen, die sich auf
        die selbe Textstellen beziehen und aufrecht erhalten bleiben, in die Quere kommen. Das kann beispielsweise
        passieren, wenn ein Satz umformuliert wird, in dem ein anderer Änderungsantrag ein Wort ersetzen will, oder ein
        Absatz gestrichen werden soll, in dem ein anderer Änderungsantrag etwas ergänzen will (und trotzdem aufrecht
        erhalten wird). In diesem Fall muss händisch nachkorrigiert werden: bei jedem kollidierende Absatz muss auf
        Basis des neuen Antragstextes der Änderungsantrag noch einmal so neu formuliert werden, wie es dem Sinn des
        ursprünglichen Änderungsantrags entspricht. Da dies nicht ganz simpel ist, empfiehlt es sich, diese Situation
        möglichst zu vermeiden – z.B. indem von vorn herein darauf geachtet wird, nur wenige sonst kollidierende
        Änderungsanträge aufrecht zu erhalten, Änderungsanträge die große Teile des Antrags ändern als Globalalternative
        markiert werden,, oder aber unstrittige Änderungsanträge so früh wie möglich zu übernehmen,
        bevor weitere Änderungsanträge eingereicht werden, die möglicherweise kollidieren.</p>

    <p>Die Möglichkeit, Änderungsanträge zu übernehmen, steht standardmäßig erst einmal nur der Veranstaltungsleitung /
        den Admins offen, kann jedoch auch in zwei Schritten den Initiator*innen des betroffenen Antrags eingeräumt
        werden:</p>
    <ul>
        <li>Im einfachen Fall können Antragsteller*innen Änderungsanträge dann selbständig übernehmen, wenn die Änderung
            unmodifiziert übernommen werden und solange es keine Kollisionen mit anderen Änderungsanträgen gibt. Alle
            anderen Änderungsanträge bleiben in diesem Fall erhalten. Hier gibt es also keine Möglichkeit für die
            Antragsteller*in, einen Änderungsantrag selbständig abzulehnen oder zu ändern.
        </li>
        <li>Im schwierigeren Fall kann die komplette Funktionalität genutzt werden, die auch Admins zur Verfügung steht.
            Da dies auch heißt, dass im Kollisionsfall die kollidierenden Änderungsanträge verändert werden können, ist
            dies nur in klar kooperativen Fällen empfehlenswert, in denen auch alle Beteiligte das oben beschriebene
            Prinzip verstehen.
        </li>
    </ul>
    <p>Die zugehörige Einstellung kann unter „Einstellungen“ → „Antragstypen bearbeiten“ → „Dürfen Antragsteller*innen
        Änderungsanträge selbständig übernehmen?“</p>

    <h3 id="alle_uebernehmen">Alle Änderungsanträge einpflegen</h3>
    <p>Sollen alle Änderungsanträge auf einmal bearbeitet und daraus die Beschlussfassung des Antrags erzeugt werden,
        gibt es hierfür einen eigenen Bearbeitungsmodus: man findet ihn beim Antrag in der Sidebar als „Änderungsanträge
        einpflegen“. Er steht den Administrator*innen der Veranstaltung zur Verfügung.</p>
    <p>Diese Funktion basiert darauf, dass der ursprüngliche Text des Antrags angezeigt wird, und sämtliche
        vorgeschlagenen Änderungen innerhalb des Textes angezeigt werden. Man geht nun Änderung für Änderung durch und
        kann die Änderung jeweils übernehmen oder Verwerfen. Vorgeschlagene Streichungen von Texten erscheinen
        beispielsweise durchgestrichen rot – übernimmt man diese Änderung, verschwindet der Text endgültig, während ein
        Verwerfen dieser Änderung die Passage wieder als regulären Text erscheinen lässt. Darüber hinaus kann der Text
        auch beliebig bearbeitet werden, um so beispielsweise redaktionelle Änderungswünsche einzupflegen.</p>
    <p>Allerdings kann es auch bei dieser Darstellungsart zu Komplikationen führen, wenn sich zwei Änderungsanträge auf
        die selbe Stelle beziehen. Antragsgrün versucht dann, möglichst viele der Änderungen innerhalb des Fließtextes
        darzustellen. Diejenigen, bei denen das nicht möglich ist, werden unterhalb des betroffenen Absatzes als
        „Kollidierender Änderungsantrag“ dargestellt. Der Konflikt muss letztlich redaktionell aufgelöst werden, der
        kollidierende Absatz anschließend gelöscht.</p>
    <p>Um die Anzahl solcher Konflikte zu minimieren, gibt es vor dem Start des Bearbeitens abgefragt, welche
        Änderungsanträge mit in die Zusammenstellung aufgenommen werden sollen – Änderungsanträge, die abgelehnt wurden
        oder Globalalternativen darstellen und daher zu einer Vielzahl an Konflikten führen können, können so von der
        Vorlage für die neue Antragsfassung ausgenommen werden.</p>
    <p>Wichtig ist noch, am Ende den neuen Status der Änderungsanträge festzulegen (angenommen, abgelehnt usw.), da das
        nicht automatisiert ermittelt werden kann. Das hat keinen direkten Einfluss auf die neue Antragsversion, sondern
        dient der Information der Antragsteller*innen.</p>

    <h4>(Öffentliche) Zwischenstände</h4>
    <p>Da das Zusammenführen der Änderungsanträge gerade bei längeren Anträgen mit vielen Änderungsanträgen einige Zeit
        in Anspruch nehmen kann, ist es hier besonders wichtig, dass im Falle eines Fehlers (Computerabsturz etc.) nicht
        die gesamte Arbeit von neuem begonnen werden muss. Daher gibt es hier Zwischenstände, die automatisch etwa
        einmal pro Minute gespeichert werden. Wird die „Anträge zusammenführen“-Seite erneut aufgerufen, ohne dass das
        Bearbeiten zuvor abgeschlossen wurde, erhält man die Möglichkeit, beim zuletzt gespeicherten Zwischenstand
        weiterzumachen.</p>
    <p><strong>Achtung:</strong> Zwischenstände können nur gespeichert werden, so lange auch Internetverbindung besteht.
    </p>

    <p>Es gibt Veranstaltungen, auf denen dieses Überarbeiten des Antrags in Beteiligung von verschiedenen Personen,
        z.B. den Antragsteler*innen geschieht. Für solche Fälle gibt es die Möglichkeit, den jeweils aktuellsten
        Zwischenstand öffentlich einsehbar zu machen, sodass alle jederzeit im Bilde sind, wie der aktuelle
        Bearbeitungstand aussieht. Dies kann während dem Einpflegen der Änderungsanträge aktiviert werden: in dem
        kleinen „Zwischenstand“-Fensterchen rechts unten im Bildschirm der Punkt „Öffentlich sichtbar“. Wird dort das
        Häkchen gesetzt, erscheint einerseits ein direkter Link auf den öffentlichen Zwischenstand, andererseits wird
        dann auch auf der regulären Antragsseite über dem Antragstext ein „Zwischenstand anzeigen“-Link eingeblendet.
        Der öffentliche Zwischenstand kann vom Betrachtenden optional so eingestellt werden, dass er sich einmal alle
        zehn Sekunden automatisch aktualisiert.</p>

    <h2 id="export">Export-Funktionen</h2>
    <h3 id="export_pdf">PDF</h3>
    <p>Sowohl Anträge als auch Änderungsanträge lassen sich automatisch in druckfertige PDF-Dokumente exportieren. Um
        die Handhabung gerade bei größeren Veranstaltungen mit mehreren hundert Anträgen zu erleichtern, gibt es nicht
        nur ein PDF pro Antrag, sondern einerseits auch immer aktuelle Sammel-PDFs mit allen Anträgen bzw.
        Änderungsanträgen, und andererseits den Download eines ZIP-Archivs aller verfügbaren Einzel-PDFs.</p>
    <p>Es werden mehrere PDF-Layouts unterstützt, die verschiedene Einsatzgebiete abdecken. Das PDF-Layout kann pro
        Antragstyp separat festgelegt werden, unter Einstellungen → Antragstypen bearbeiten → PDF-Layout. Falls Sie für
        Ihre Veranstaltung ein spezielles PDF-Template benötigen, können wir gerne bei der Umsetzung helfen.</p>

    <h3 id="export_opendocument">OpenDocument / Textverarbeitungsdokument</h3>
    <p>Anträge und Änderungsanträge können auch im OpenDocument-Format (.odt) exportiert werden, womit die Texte mitsamt
        allen Formatierungen in gängigen Textverarbeitungsprogrammen weiterbearbeitet werden können.</p>
    <p>Den Export gibt es für Admins der Veranstaltung im Menüpunkt „Antragsliste“.</p>

    <h3 id="export_tabelle">Änderungsanträge in Tabellenform</h3>
    <p>Manche Programmkommissionen bevorzugen eine Darstellung aller Änderungsanträge einer Veranstaltung mitsamt den
        beantragten Änderungen in einem Tabellendokument. Dafür unterstützt Antragsgrün den Export aller
        Änderungsanträge in ein OpenDocument Spreadsheet-Dokument, das z.B. mit OpenOffice oder LibreOffice leicht
        bearbeitet werden kann.</p>
    <p>Den Export gibt es für Admins der Veranstaltung im Menüpunkt „Antragsliste“.</p>

    <h3 id="export_openslides">Openslides / CSV</h3>
    <p>Die OpenSource-Software OpenSlides wird von vielen Organisationen gerne für die Veranstaltungsorganisation direkt
        vor Ort eingesetzt, z.B. um Anträge auf dem Beamer darzustellen, Redelisten zu führen und Wahlen zu
        protokollieren. Daher bietet Antragsgrün auch einen CSV-Export, der speziell auf das Import-Format von
        OpenSlides angepasst ist. Anträge und Änderungsanträge, die im Vorfeld einer Tagung in Antragsgrün eingereicht
        und vorbereitet wurden, können so kurz vor der Tagung leicht in einen bestehenden Openslides-Ablauf integriert
        werden.</p>
    <p>Den Export gibt es für Admins der Veranstaltung im Menüpunkt „Antragsliste“.</p>

    <h3 id="export_weitere">HTML, Plain Text, RSS, Weitere Formate</h3>
    <p>Generell ist es recht leicht, weitere Formate zu unterstützen. Ein paar weitere Formate werden daher bereits
        unterstützt: z.B. ein Export in reine formlose HTML-Seiten, in Plain-Text, und auch RSS-Feeds der aktuellen
        Anträge gibt es.</p>
    <p>Falls weitere Formate benötigt werden, kontaktieren Sie uns einfach.</p>

    <h2 id="weitere_funktionen">Weitere Funktionen</h2>
    <h3 id="layout">Layout-Anpassbarkeit</h3>
    <p>Verschiedene Aspekte des Layouts von Antragsgrün lassen sich über das Administrationsinterface anpassen – die
        meisten davon unter „Einstellungen“ → „Diese Veranstaltung“ → „Aussehen“.</p>
    <p>Am stärksten wirkt sich die „Layout“-Einstellung aus: sie verändert das komplette Aussehen der Seite und wird
        genutzt, um eine entwickelte Anpassung an ein Corporate Design zu aktivieren. Neben dem
        „Antragsgrün-Standard“-Layout gibt es derzeit Layouts, die teils für den Deutschen Bundesjugendring, teils für
        Bündnis 90 / Die Grünen entwickelt wurden. Weitere grundlegend andere Layouts können entweder von halbwegs
        versierten Web-Entwickler*innen selbst entwickelt werden (siehe „<a
                href="https://github.com/CatoTH/antragsgruen">Developing custom themes</a>“), oder bei uns in Auftrag
        gegeben werden.</p>
    <p>Für den Aufbau der Startseite gibt es mehrere Varianten („Startseiten-Design“ in den Einstellungen), die sich für
        verschiedene Einsatzzwecke eignen. Insbesondere kann darüber eingestellt werden, ob eine Tagesordnung auf der
        Startseite angezeigt werden soll, wie die Anträge in Bezug zur Tagesordnung angezeigt werden sollen, oder ob die
        Anträge nach selbst definierten Schlagworten bzw. Themen gegliedert werden sollen (siehe weiter unten im Punkt
        „Themen“).</p>
    <p>Darüber hinaus lässt sich ein eigenes Logo verlinken und verschiedene Aspekte der Seite lassen sich
        ausblenden.</p>

    <h3 id="zeilennummerierung">Zeilennummerierung</h3>
    <p>Für viele Organisationen, die mit vielen Anträgen arbeiten, ist ein konsistentes Zeilennummerierungssystem
        essenziell wichtig. Bei Antragsgrün wird daher großer Wert darauf gelegt, das überall zu berücksichtigen: die
        maximale Länge einer Zeile lässt sich einmal festlegen („Einstellungen“ → „Diese Veranstaltung“ → „Maximale
        Zeilenlänge“) und wird überall berücksichtigt: bei der Anzeige der Anträge, dem PDF-Download und dem Export in
        Textverarbeitung-Dokumente, und auch in der Einleitung von Änderungsanträgen („Einfügung in Zeile ##“) wird die
        Zeilennummerierung automatisch richtig ermittelt, um Fehler zu vermeiden.</p>
    <p>Wird über Antragsgrün ein Programmentwurf diskutiert, der in mehrere Kapitel unterteilt wird, die aber
        durchgehend durchnummeriert werden sollen, kann dies explizit aktiviert werden: unter „Einstellungen“ → „diese
        Veranstaltung“ → „Anträge“ → „Zeilennummerierung durchgehend für die ganze Veranstaltung“.</p>

    <h3 id="redaktionelle_aes">Redaktionelle Änderungsanträge</h3>
    <p>Für manche Änderungsanträge eignet sich das „eigentliche“ Prinzip der Änderungsanträge bei Antragsgrün nur
        eingeschränkt: sollen beispielsweise in einem gesamten langen Antrag sämtliche Vorkommnisse eines Begriffs durch
        einen anderen Begriff ersetzt werden, wäre es mühselig und unübersichtlich, als Antragsteller*in jeden Begriff
        einzeln zu ersetzen. Für solche Fälle gibt es die Möglichkeit, redaktionelle Änderungsanträge zu stellen: hier
        wird die beantragte Änderung in normaler Anweisungsform geschrieben, und im Falle einer Übernahme obliegt es der
        Antragskommission, die Änderungen tatsächlich durchzuführen. Eine automatisch Übernahme ist damit natürlich
        nicht möglich.</p>
    <p>Sind solche Änderungsanträge nicht gewünscht, lassen sie sich auch aktivieren: „Einstellungen“ → „Diese
        Veranstaltung“ → „Änderungsanträge“ → „Redaktionelle Änderungsanträge zulassen“.</p>

    <h3 id="antragskuerzel">Antragskürzel</h3>
    <p>Es ist gängige Praxis bei vielen Konferenzen, dass alle Anträge und Änderungsanträge eindeutige Kürzel versehen
        bekommen – z.B. „A1“ für Antrag Nr. 1 oder „Ä2“ für Änderungsantrag Nr. 2. Antragsgrün unterstützt sowohl die
        automatische als auch händische Vergabe dieser Kürzel nach verschiedenen Schemata.</p>
    <p>Für Anträge wird im Antragstyp ein Basis-Kürzel gesetzt, im oberen Fall z.B. „A“. Alle Anträge dieses Typs, die
        eingereicht werden, werden standardmäßig damit durchnummeriert – also „A1“, „A2“ usw.. Das Kürzel eines Antrags
        kann jederzeit vom Admin geändert werden – es darf allerdings jederzeit nur einen einzigen Antrag mit einem
        bestimmten Kürzel geben. Soll verschiedene Basis-Kürzel geben wie z.B. „A“ für reguläre Anträge und „D“ für
        Dringlichkeitsanträge und diese Kürzel automatisch vergeben werden, müssen dafür mehrere Antragstypen angelegt
        werden.</p>
    <p>Für Änderungsanträge gibt es verschiedene Schemata, die unter „Einstellungen“ → „Diese Veranstaltung“ →
        „Änderungsanträge“ → „Nummerierung“ ausgewählt werden können. Zur Auswahl steht:</p>
    <ul>
        <li>Eine einfache durchgehende Nummerierung aller Änderungsanträge („Ä1“, „Ä2“, …)</li>
        <li>Eine durchgehende Nummerierung pro Antrag („Ä1 zu A1“, „Ä2 zu A1“, „Ä1 zu A2“, …)</li>
        <li>Eine Nummerierung, die sich an der Zeilennummer der ersten zu ändernden Zeile des Antrags anlehnt („A1-23“,
            wenn Zeile 23 die erste ist, die geändert werden soll)
        </li>
    </ul>

    <h3 id="themen">Themen / Tags</h3>
    <p>Anträge lassen sich auf der Startseite nicht nur streng hierarchisch in einer Tagesordnung darstellen, sondern
        auch nach nach vorgegebenen Schlagworten gliedern. Der wichtigste Unterschied dabei ist, dass ein Antrag auch
        mehrere Themen / Schlagworte gleichzeitig haben kann (auf der Startseite also z.B. sowohl unter „Verkehr“ als
        auch „Umwelt“ auftauchen kann). Welche Themen zur Auswahl stehen, kann von der Leitung der Veranstaltung
        vorgegeben werden. Antragsteller*innen können beim Einreichen eines Antrags Themen auswählen.</p>
    <p>Man aktiviert diese Darstellung unter „Einstellungen“ → „Diese Veranstaltung“, indem man zuerst bei
        „Startseiten-Design“ den Punkt „Themen / Schlagworte“ wählt und weiter unten bei „Anträge“ die verschiedenen
        Themen anlegt, die zur Auswahl stehen sollen. Im allgemeinen wird auch die Einstellung „Mehrere Themen pro
        Antrag möglich“ gleich darunter empfehlenswert sein.</p>

    <h3 id="kommentare">Kommentare</h3>
    <p>Sowohl Anträge als auch Änderungsanträge können kommentiert werden, sofern dies von der Veranstaltungsleitung
        nicht deaktiviert wird. Generell lässt sich pro Antragstyp festlegen, ob Kommentare möglich sein sollen (z.B.
        kann dies bei Anträgen aktiviert und bei Bewerbungen deaktiviert werden) und ob für Kommentar ein Login
        notwendig sein soll oder nicht. Diese Einstellung findet sich unter „Einstellungen“ → „Antragstypen bearbeiten“
        → „Berechtigungen“. Um das Kommentieren zu deaktivieren,
        stellt man bei „Kommentieren“ einfach „Niemand“ ein.</p>
    <p>Bei Anträgen ist es außerdem auch möglich, dass der Antragstext absatzweise kommentiert wird, nicht nur als
        Ganzes. Dadurch ist es gerade bei längeren Anträgen leichter zu erkennen, welche Abschnitte besonders stark
        diskutiert werden, bzw. auf welchen Teil des Antrags sich ein Kommentar bezieht. Diese Möglichkeit muss von der
        Veranstaltungsleiter allerdings erst explizit freigegeben werden: in den Einstellungen des Antragstyps gibt es
        weiter unten die Übersicht der „Antrags-Abschnitte“. Im „Antragstext“ kann man unter „Kommentare“ dafür „Pro
        Absatz“ auswählen.</p>
    <p>Grundsätzlich lässt sich auch einstellen, dass Kommentare erst freigeschaltet werden müssen, bevor sie für alle
        sichtbar sind. Das ist vor allem dann interessant, wenn Kommentare nicht an ein Login gebunden sind. Dies wird
        global für die ganze Veranstaltung eingestellt, unter „Einstellungen“ → „Diese Veranstaltung“ → „Kommentare“ →
        „Freischaltung von Kommentaren“. Hier lässt sich außerdem auch einstellen, ob die Angabe einer
        Kontakt-E-Mail-Adresse erzwungen werden soll.</p>

    <h3 id="zustimmung">Zustimmung / Ablehnung</h3>
    <p>Auf Wunsch kann Besucher*innen der Seite die Möglichkeit gegeben werden, Anträgen die Zustimmung oder Ablehnung
        auszudrücken. Die Möglichkeit kann pro Antragstyp sowohl für die Anträge als auch für die zugehörigen
        Änderungsanträge jeweils einzeln aktivieren oder deaktivieren. Dazu wählt man unter „Einstellungen“ →
        „Antragstyp bearbeiten“ → „Berechtigungen“ bei „Anträge unterstützen“ bzw. „Änderungsanträge unterstützen“ aus,
        wer diese Funktion alles nutzen können soll, und ob es nur Zustimmung, Ablehnung oder beides geben soll. (Das
        „Offiziell unterstützen“ spielt in diesem Zusammenhang keine Rolle, sondern wird für die „Unterstützung
        sammeln“-Phase verwendet, die weiter oben beschrieben wurde)</p>

    <h3 id="uebersetzen">Übersetzung der Seite / Anpassungen des Wordings</h3>
    <p>Antragsgrün unterstützt drei Stufen der sprachlichen Anpassung:</p>
    <ul>
        <li>Pro Veranstaltung lassen sich sämtliche Texte auf der Oberfläche von Antragsgrün über die
            Administrations-Oberfläche anpassen, unter „Einstellungen“ → „Sprache anpassen“. Diese Möglichkeit lässt
            sich beispielsweise dazu verwenden, einzelne Begriffe anzupassen, E-Mail-Texte zu ändern, vom „Du“ auf das
            „Sie“ zu wechseln oder eine andere Form der geschlechtergerechten Sprache einzubauen.
        </li>
        <li>Komplette Übersetzungen: Antragsgrün ist darauf ausgelegt, in beliebige Sprachen übersetzt werden zu können
            – aktuell gibt es eine englische, deutsche und französische Sprachfassung. Dies ist allerdings nicht mehr über eine
            Web-Oberfläche möglich, sondern erfordert Eingriffe in den Programmcode. Falls Sie eine Übersetzung planen,
            kontaktieren Sie uns einfach, wir helfen hier gerne (und würden uns freuen, wenn die Übersetzung dann auch
            Teil des Open-Source-Projekts werden könnte).
        </li>
        <li>
            Als Mittelding zwischen den beiden Lösungen gibt es noch die Möglichkeit, Sprachvarianten anzulegen – also
            Übersetzungen, die im großen und ganzen einer regulären Sprache entsprechen, aber nur einzelne Begriffe
            ändern. Auch über diesen Mechanismus wäre eine „Du“ → „Sie“-Übersetzung denkbar, oder natürlich
            Unterscheidungen wie „Englisch (britisch)“ und „Englisch (US)“. Auch diese Variante erfordert einen Eingriff
            in den Programmcode. Der wichtigste Unterschied zur Eingabe über die Web-Oberfläche (Punkt 1) ist, dass die
            Übersetzung dann allen Veranstaltungen zur Verfügung steht, und Bestandteil der offiziellen
            Antragsgrün-Distribution werden kann.<br>
            Welche Sprachvariante verwendet werden soll, kann pro Veranstaltung unter „Einstellungen“ → „Sprache
            anpassen“ → „Basis-Sprachversion“ ausgewählt werden.
        </li>
    </ul>

</div>