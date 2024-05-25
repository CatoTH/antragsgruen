<?php
use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün - die grüne Online-Antragsverwaltung';
/** @var \app\controllers\Base $controller */
$controller  = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl      = 'https://antragsgruen.de/help';
$controller->layoutParams->alternateLanuages = ['en' => 'https://motion.tools/help'];
$controller->layoutParams->addBreadcrumb('Start', '/');
$controller->layoutParams->addBreadcrumb('Hilfe');

?>
<h1>Antragsgrün - das grüne Antragstool</h1>

<div class="content managerHelpPage">

    <h2>Antragsgrün - Funktionsübersicht und Handbuch</h2>

    <ul class="toc">
        <li>
            <strong>Anleitungen für konkrete Anwendungsfälle</strong>
            <ul>
                <li><a href="/help/member-motion">Mitglieder reichen Anträge ein</a></li>
                <li><a href="/help/amendments">Änderungsanträge einreichen</a></li>
                <li><a href="/help/progress-reports">Sachstandsberichte</a></li>
            </ul>
        </li>
        <li>
            <a href="#grundlegender_aufbau"
               onClick="$('#grundlegender_aufbau').scrollintoview({top_offset: -30}); return false;">Grundlegender
                Aufbau einer Antragsgrün-Seite</a>
            <ul>
                <li><a href="#antraege" onClick="$('#antraege').scrollintoview({top_offset: -30}); return false;">Anträge / Änderungsanträge</a></li>
                <li><a href="#veranstaltungen" onClick="$('#veranstaltungen').scrollintoview({top_offset: -30}); return false;">Veranstaltungen</a></li>
                <li><a href="#antragstypen" onClick="$('#antragstypen').scrollintoview({top_offset: -30}); return false;">Antragstypen</a></li>
                <li><a href="#abschnittstypen" onClick="$('#abschnittstypen').scrollintoview({top_offset: -30}); return false;">Antrags-Abschnitte</a></li>
                <li><a href="#tagesordnung" onClick="$('#tagesordnung').scrollintoview({top_offset: -30}); return false;">Tagesordnungspunkte</a></li>
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
            <a href="#abstimmungen"
               onClick="$('#abstimmungen').scrollintoview({top_offset: -30}); return false;">Abstimmungen</a>
            <ul>
                <li><a href="#abstimmungen_grenzen" onClick="$('#abstimmungen_grenzen').scrollintoview({top_offset: -30}); return false;">Grenzen</a></li>
                <li><a href="#abstimmungen_administration" onClick="$('#abstimmungen_administration').scrollintoview({top_offset: -30}); return false;">Verwaltung</a></li>
                <li><a href="#abstimmungen_user" onClick="$('#abstimmungen_user').scrollintoview({top_offset: -30}); return false;">Aus Sicht des Abstimmenden</a></li>
            </ul>
        </li>
        <li>
            <a href="#weitere_funktionen"
               onClick="$('#weitere_funktionen').scrollintoview({top_offset: -30}); return false;">Weitere
                Funktionen</a>
            <ul>
                <li><a href="#user_administration" onClick="$('#user_administration').scrollintoview({top_offset: -30}); return false;">Benutzer*innen-Verwaltung</a>
                <li><a href="#layout" onClick="$('#layout').scrollintoview({top_offset: -30}); return false;">Layout-Anpassbarkeit</a>
                </li>
                <li><a href="#zeilennummerierung"
                       onClick="$('#zeilennummerierung').scrollintoview({top_offset: -30}); return false;">Zeilennummerierung</a>
                </li>
                <li><a href="#redaktionelle_aes"
                       onClick="$('#redaktionelle_aes').scrollintoview({top_offset: -30}); return false;">Redaktionelle
                        Änderungsanträge</a></li>
                <li><a href="#antragskuerzel" onClick="$('#antragskuerzel').scrollintoview({top_offset: -30}); return false;">Antragskürzel</a></li>
                <li><a href="#antragsversionen" onClick="$('#antragsversionen').scrollintoview({top_offset: -30}); return false;">Antragsversionen</a></li>
                <li><a href="#themen" onClick="$('#themen').scrollintoview({top_offset: -30}); return false;">Themen / Tags</a></li>
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
        und formalisiert diese daher stark: beim Einreichen eines Änderungsantrags muss explizit angegeben werden, wie
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

    <h3 id="antragstypen">Antragstypen</h3>
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

    <h3 id="abschnittstypen">Antrags-Abschnitte</h3>

    <p>Jeder Antragstyp gibt eine Struktur vor, in der Dokumente dieses Types eingereicht werden. Im einfachsten Fall kann dies ein Titel und ein Textfeld sein, es werden aber auch angehängte Bilder, PDFs, tabellarische Angaben, eingebettete Videos und mehr unterstützt. Dadurch lassen sich auch etwas komplexere Eingabeformulare gestalten.</p>

    <p>Konkret stehen folgende Antrags-Abschnitte zur Auswahl:</p>
    <ul>
        <li><strong>Titel:</strong> ein einzeiliges Textfeld. Üblicherweise der erste Abschnitt eines Dokuments, eben um den Titel desselben festzulegen (oder auch den Namen der Bewerberin bei Bewerbungen). Es kann aber auch mehrere Titel-Felder geben - beispielsweise die Adressatin bei Petitionen.</li>
        <li><strong>Text:</strong> Freitext mit einer Reihe an Formatierungsmöglichkeiten. Dieser Abschnitts-Typ wird in der Regel das „Herz“ eines Antrags darstellen, da zu diesem Abschnittstyp auch Änderungsanträge gestellt werden können.</li>
        <li><strong>Text (erweitert):</strong> Ein Freitextfeld mit erweiterten Formatierungsmöglichkeiten. Diese erweiterten Möglichkeiten kommen mit einem Preis: der eingegebene Text kann nicht auf alle Weisen exportiert werden, auch Änderungsanträge darauf sind nicht möglich.</li>
        <li><strong>Redaktionell bearbeitbarer Text:</strong> Ein Freitextfeld, dessen Inhalt einen eher dynamischen Kommentar darstellt, der seltener von einer Antragsteller*in bereitgestellt wird, sondern eher von redaktioneller Seite. Ein typischer Anwendungsfall sind Sachstandsberichte, bei dem unter einem offiziellen Beschlusstext (Typ: „Text“) ein Sachstand dargestellt wird. Neben Administrator*innen können dieses Textfeld alle Benutzer*innen der Gruppe „Sachstände bearbeiten“ bzw. mit dem Recht „Redaktionelle Antragsabschnitte / Sachstände bearbeiten“ direkt in der Antrags-Ansicht bearbeiten.</li>
        <li><strong>Bild:</strong> Die Antragsteller*in kann hier ein Bild im PNG, JPEG oder GIF-Format hochladen.</li>
        <li><strong>Tabellarische Angaben:</strong> Hier kann eine Tabelle mit verschiedenen anzugebenden Daten vorgegeben werden. Als Datentypen können Freitext, Numerische Werte, Datum und ein Auswahlfeld mit vorgegebenen Auswahlmöglichkeiten festgelegt werden.</li>
        <li><strong>PDF-Anhang:</strong> Die Antragsteller*in kann ein PDF hochladen, das innerhalb des Antrags bzw. dargestellt werden. Dies kann beispielsweise genutzt werden, um bei Geschäftsberichten komplexere Tabellen anzuhängen, bzw. um Bewerber*innen die Möglichkeit zu geben, ein vorformatiertes PDF hochzuladen.</li>
        <li><strong>Alternatives PDF:</strong> Auch hier kann ein PDF hochgeladen werden, allerdings mit einem anderen Zweck: hier ersetzt das hochgeladene das von Antragsgrün sonst automatisch erzeugte PDF des Antrags. Dies kann dazu genutzt werden, wenn die heruntergeladene Version des Antrags speziell gelayoutet werden soll.</li>
        <li><strong>Eingebettetes Video:</strong> Hier können Links zu Videos auf Videoseiten eingestellt werden. Dies kann beispielsweise bei Bewerbungen genutzt werden, um Antragsteller*innen die Möglichkeit zu geben, der Bewerbung eine Selbstvorstellung in Videoform beizufügen. Der Video-Upload muss erfolgt allerdings auf einer separaten Seite erfolgen. Im Falle von Vimeo oder Youtube wird das Video direkt im Antrag eingebettet, ansonsten wird es verlinkt.</li>
    </ul>

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
    <p>Um Tagesordnungen zu aktivieren: Erst unter „Einstellungen“ in der Unterseite „Aussehen und Bestandteile der Seite“ bei
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
        die Authentifizierung auf weitere Mechanismen ausweiten; produktiv kamen Bereits OpenID- und SAML-Konnektoren
        sowie Integrationen in CRM-Systeme (z.B. CiviCRM) zum Einsatz. Für weitere Informationen schreiben Sie uns bitte einfach an.</p>

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
    <p>
        Bei der Entscheidung zwischen den beiden Möglichkeiten ist ein Punkt besonders wichtig zu beachten:
        werden alle Änderungsanträge auf einmal eingepflegt, <em>kann</em> dieser Vorgang im Nachhinein <strong>rückgängig gemacht</strong> werden.
        Wird nur ein einzelner Änderungsantrag eingepflegt, kann dies <em>nicht</em> rückgängig gemacht werden.
    </p>
    <p>Wichtig als Vorbemerkung hierzu ist außerdem zu wissen: wenn es zwei oder mehr Änderungsanträge gibt, die Änderungen
        an der selben Textstelle vornehmen wollen, kommt es hier zu Konflikten zwischen diesen Änderungsanträgen, die
        händisch aufgelöst werden müssen – was oft nicht ganz trivial ist.</p>
    <p>Das grundsätzliche Prinzip bei beiden Varianten ist dabei: durch die Übernahme des Änderungsantrags bzw. der
        Änderungsanträge entsteht eine neue Version des Antrags mit dem neuen Text. Die alte Version bleibt zusammen mit
        den Änderungsanträgen erhalten, sodass transparent bleibt, was sich an einem Antrag geändert hat. Wenn nur ein
        einzelner Änderungsantrag übernommen wird, verbleiben diejenigen anderen Änderungsanträge bei der alten Version,
        die in diesem Zuge als abgelehnt oder (Modifiziert) Angenommen markiert wurden, während diejenigen, die auf
        „Unverändert: Eingereicht“ verbleiben mit in die neue Version des Antrags übernommen werden.</p>
    <p>
        Als Ergebnis des Einpflegens entsteht eine neue <a href="#antragsversionen">Antragsversion</a>.
        Die bisherige Version bleibt bestehen, wird aber standardmäßig nicht mehr angezeigt.
    </p>

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

    <h4>Einpflegen rückgängig machen</h4>
    <p>
        Soll der Ursprungszustand wiederhergestellt werden - also der Antrag mit allen Änderungsantägen - und die
        erarbeitete und veröffentlichte neue Version / Beschlussfassung verworfen werden, ist das vorgehen wie folgt:
        zunächst wird die neu erzeugte Version des Antrags entweder gelöscht, oder auf einen unsichtbaren Status
        wie „Entwurf (Admin)” gesetzt. In letzterem Fall muss außerdem noch das Feld „Überarbeitete Fassung von”
        geleert werden. Schließlich muss der ursprüngliche Antrag über die Administration vom Status „Modifiziert”
        in „Eingereicht” zurückgesetzt werden.
    </p>

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
    <p>Den Export gibt es für Admins der Veranstaltung im Menüpunkt „Antragsliste“, nachdem der Export einmalig auf dieser Seite
        unter „Funktionen” → „Openslides-Export” aktiviert wurde.</p>
    <p>Zusätzlich kann Antragsgrün mit etwas Aufwand so konfiguriert werden, dass die selbe Benutzer*innen-Verwaltung
        wie OpenSlides verwendet wird, um nicht zwei Zugänge pro Mitglied verwalten zu müssen. Falls diese Funktion
        benötigt wird, kontaktieren Sie uns.</p>

    <h3 id="export_weitere">HTML, Plain Text, RSS, Weitere Formate</h3>
    <p>Generell ist es recht leicht, weitere Formate zu unterstützen. Ein paar weitere Formate werden daher bereits
        unterstützt: z.B. ein Export in reine formlose HTML-Seiten, in Plain-Text, und auch RSS-Feeds der aktuellen
        Anträge gibt es.</p>

    <h2 id="abstimmungen">Abstimmungen</h2>

    <p>Auf Antragsgrün können Abstimmungen über Anträge, Änderungsanträge und einfache Fragen abgehalten werden.
        Es kann flexibel eingerichtet werden, sowohl was die Berechtigung zur Stimmabgabe, die Sichtbarkeit der Stimmen
        und die genaue Art der Mehrheits angeht.<br>
        Das Abstimmungssystem kann auch dafür verwendet werden, die Anwesenheit der Mitglieder abzufragen.
        Es ist dabei darauf ausgerichtet, den Benutzer*innen das Abstimmen so leicht wie möglich zu machen.</p>

    <h3 id="abstimmungen_grenzen">Grenzen</h3>

    <p>Eine geheimes Wahlsystem ist technisch hoch komplex (oder unmöglich), weshalb Antragsgrün nicht für Personenwahlen eingesetzt werden darf.</p>

    <h3 id="abstimmungen_administration">Verwaltung</h3>

    <h4>Abstimmungsblöcke</h4>

    <p>Ein Abstimmungsblock ist eine Sammlung von (Änderungs-)Anträgen und Fragen, für welche die selben Regeln gelten
        (Öffentlichkeit, Abstimmungsrecht, Mehrheitsprinzip, ...).
        Er wird als Ganzes entweder auf der Startseite oder auf einer Antragsseite angezeigt.
        Ein Abstimmungsblock hat demnach einen Namen, ein zugeordnetes Mehrheitsprinzip,
        Sichtbarkeitseinstellungen, bei Bedarf eine Zuordnung zu einem Antrag,
        und eine protokollierbare Zahl an anwesenden Mitgliedern.</p>

    <p>Abstimmungsblöcke können sich in folgendem Zustand befinden:</p>
    <ul>
        <li><strong>Offline</strong>: Die Abstimmung wird bei Bedarf im Rahmen eines Verfahrensvorschlags angezeigt, die eigentliche Abstimmung erfolgt aber nicht über Antragsgrün.</li>
        <li><strong>Vorbereitung</strong>: Die Online-Abstimmung soll genutzt werden, ist aber noch nicht eröffnet. In diesem Status können neue (Änderungs-)Anträge hinzugefügt oder wieder entfernt werden. Die Abstimmung ist für reguläre Benutzer*innen noch nicht sichtbar.</li>
        <li><strong>Offen</strong>: Die Abstimmung ist sichtbar, Benutzer*innen können Stimmen abgeben. Es können mehrere Blöcke gleichzeitig offen sein, auch wenn leicht unübersichtlich werden dürfte.</li>
        <li><strong>Geschlossen</strong>: Keine neuen Stimmen können abgegeben werden. Abhängig von der gewählten Mehrheitsformel werden die (Änderungs-)Anträge mit genügend Ja-Stimmen auf „Angenommen”, alle anderen auf „Abgelehnt” gesetzt. Geschlossene Abstimmungen sind auf einer separaten Seite weiterhin einsehbar (was aber noch nicht umgesetzt ist).</li>
    </ul>
    <p>Geschlossene Abstimmungen können wieder geöffnet werden, und durch „Zurücksetzen” auch wieder auf „Vorbereitung” gesetzt werden. In letzterem Fall gehen allerdings alle bis dahin abgegebenen Stimmen verloren.</p>

    <p>Zu Beginn gibt es noch keine Abstimmungsblöcke. Sie können entweder auf der Administrationsseite (Einstellungen → Abstimmungen) oder auf der Bearbeitungsseite eines (Änderungs-)Antrags angelegt werden (siehe weiter unten).</p>

    <p>Genauere Einstellungen zum Abstimmungblock sowie die Möglichkeit ihn wieder zu löschen finden sich, wenn man neben dem Titel des Blocks auf das Einstellungs-Icon klickt. Insbesondere gibt es folgende Einstellungsmöglichkeiten:</p>

    <p><strong>Antwortmöglichkeiten:</strong></p>
    <ul>
        <li><strong>Ja, Nein, Enthaltung</strong> (Der Standard)</li>
        <li><strong>Ja, Nein</strong> (Keine explizite Enthaltung möglich)</li>
        <li><strong>Anwesend</strong> - Hier steht nur eine Antwortmöglichkeit zur Auswahl, die dazu genutzt werden kann, die Anwesenheit von Mitgliedern abzufragen.</li>
    </ul>

    <p><strong>Mehrheitsprinzip:</strong></p>
    <ul>
        <li><strong>Einfache Mehrheit</strong>: Ein Antrag gilt als angenommen, wenn die Zahl der Ja-Stimmen die der Nein-Stimmen übersteigt. Enthaltungen werden nicht mitgezählt.</li>
        <li><strong>Absolute Mehrheit</strong>: Ein Antrag gilt als angenommen, wenn die Zahl der Ja-Stimmen die der Nein-Stimmen und Enthaltungen zusammen übersteigt.</li>
        <li><strong>2/3-Mehrheit</strong>: Ein Antrag gilt als angenommen, wenn mindestens doppelt so viele Ja- wie Nein-Stimmen abgegeben werden. Enthaltungen werden nicht mitgezählt.</li>
    </ul>
    <p><strong>Öffentlichkeit:</strong></p>
    <ul>
        <li><strong>Abstimmungsergebnisse:</strong> Die Abstimmungsergebnisse können entweder für alle oder nur für Administrierende sichtbar sein.</li>
        <li><strong>Einzelstimmen:</strong> Standardmäßig sind abgegebene Stimmen geheim. Es kann aber auch eine öffentliche Stimmabgabe oder eine nur für Administrierende sichtbare eingestellt werden. Vor der Stimmabgabe ist sichtbar, welche Sichtbarkeit gesetzt ist. Diese Einstellung kann nachträglich nicht geändert werden.</li>
    </ul>
    <p><strong>Berechtigung zur Stimmabgabe:</strong><br>Standardmäßig können alle registrierten Benutzer*innen abstimmen, die auf diese Veranstaltung Zugriff haben.
       Allerdings kann auch das Gruppen-System genutzt werden, um die Berechtigung genauer auszudifferenzieren. Dazu wählt man „Ausgewählte Gruppen” und dann eine oder mehrere vorher angelegte Benutzer*innen-Gruppen.</p>

    <h4>Eine einzelne Frage abstimmen lassen</h4>

    <p>Um eine einfache Frage abzustimmen, die nicht direkt mit der Annahme oder Ablehnung eines (Änderungs-)Antrags zu tun hat, kann man den Button „Frage hinzufügen” am Ende eines jeden Abstimmungsblocks verwenden und dort die Fragestellung eingeben.</p>
    <p>Beispielsweise kann dies eingesetzt werden, um über die Tagesordnung abzustimmen (Antwortmöglichkeiten: Ja, Nein, Enthaltung) or die Anwesenheit abzufragen (Antwortmöglichkeit: Anwesend).</p>

    <h4>Einen Antrag oder Änderungsantrag abstimmen lassen</h4>

    <p>Am einfachsten kann man einen (Änderungs-)Antrag einer Abstimmung hinzufügen, indem man die „(Änderungs-)Antrag hinzufügen”-Funktion am Ende eines jeden Abstimmungsblocks verwendet. Abgesehen von einzelnen (Änderungs-)Anträgen kann man hier auch gesammelt alle Änderungsanträge eines Antrags auf einmal hinzufügen.</p>

    <p>Auch auf der Bearbeitungsseite eines (Änderungs-)Antrags kann man ihn einer Abstimmung hinzufügen, indem man zunächst den Status (Hauptstatus oder Verfahrensvorschlag) auf „Abstimmung” setzt. Es erscheinen daraufhin weitere Einstellungsmöglichkeiten, mit denen man ihn einem existierenden Abstimmungsblock zuordnen kann oder einen neuen anlegen.</p>

    <p>Auf der Bearbeitungsseite eines (Änderungs-)Antrags kann man Anträge auch „<strong>gruppieren</strong>”. Sind Änderungsanträge gruppiert, erhalten diese immer die selbe Stimme - also immer entweder alle ein „Ja” oder alle ein „Nein”. Das ist dann sinnvoll, wenn zwei oder mehr Änderungsanträge voneinander abhängen, der eine ohne den anderen also keinen Sinn ergibt.</p>

    <p>Als „Abstimmungsstatus” setzt man zu Beginn am besten „Abstimmung”, um anzudeuten, dass die Entscheidung noch aussteht. Sobald die Abstimmung geschlossen wird, wird dieser Status automatisch auf „Angenommen” oder „Abgelehnt” gesetzt.</p>

    <p>Nennenswerte <strong>Einschränkungen</strong>: Jeder Antrag und Änderungsantrag kann nur einem Abstimmungsblock gleichzeitig zugeordnet sein. Außerdem kann man sie nur zuordnen und entfernen, wenn der betreffende Block entweder im „Offline”- oder „Vorbereitung”-Status ist.</p>

    <h3 id="abstimmungen_user">Aus Sicht des Abstimmenden</h3>

    <p>Die Abstimmung findet entweder auf der Startseite oder auf einer speziellen Antragsseite statt - abhängig davon, wie es vom Admin eingerichtet wurde. Standardmäßig ist keine Abstimmung sichtbar. Eine Abstimmung wird genau dann sichtbar, wenn ein Admin im jeweiligen Abstimmungsblock die Abstimmung eröffnet.</p>

    <p>Nun können Benutzer*innen für jeden Antrag oder Änderungsantrag mit Ja, Nein oder Enthaltung stimmen. Sind mehrere (Änderungs-Anträge) gruppiert, werden sie zusammen angezeigt und es gibt nur je einen Ja/Nein/Enthaltungs-Button, der dann für alle gilt.</p>

    <p>Solange die Abstimmung offen ist, können Nutzer*innen ihre Stimme auch wieder zurücknehmen und sich umentscheiden. Sobald die Abstimmung geschlossen wird, gibt es diese Möglichkeit nicht mehr, die Astimmung verschwindet von der Seite. Sie ist weiterhin auf einer separaten Seite sichtbar.</p>

    <h2 id="weitere_funktionen">Weitere Funktionen</h2>

    <h3 id="user_administration">Benutzer*innen-Verwaltung</h3>

    <p>Die Benutzer*innen-Verwaltung (unter „Einstellungen” → „Registrierte Benutzer*innen”) kann für Mehreres verwendet werden:</p>
    <ul>
        <li>Neue Administrator*innen eintragen</li>
        <li>Den Zugang zur Seite verwalten (sofern dies aktiviert wurde)</li>
        <li>Gruppen definieren, Mitgliedern einer Gruppe bestimmte Rechte zu geben (z.B. an Abstimmungen teilzunehmen oder Anträge anzulegen)</li>
    </ul>

    <p>Standardmäßig kann eine Antragsgrün-Seite von allen (lesend) geöffnet werden, und jede und jeder kann sich registrieren. Mit zwei Einstellungsmöglichkeiten (unter „Diese Veranstaltung” → „Zugang zur Veranstaltung”) kann man dies anpassen:</p>
    <ul>
        <li>&ldquo;Nur eingeloggte Benutzer*innen dürfen zugreifen (inkl. lesen)&rdquo;. Hiermit kann auch der lesende Zugriff auf registrierte Nutzer*innen eingeschränkt werden. Wenn nur dies gesetzt wird, ist die Registrierung aber immer noch möglich, weswegen dann oft auch die folgende Einstellung gesetzt wird:</li>
        <li>&ldquo;Nur ausgewählten Benutzer*innen das Login erlauben&rdquo;. Wenn dies aktiv ist, haben Administrator*innen genaue Kontrolle darüber, wer auf die Seite zugreifen können und wer nicht.</li>
    </ul>

    <p>Wenn &ldquo;<strong>Nur ausgewählten Benutzer*innen das Login erlauben</strong>&rdquo; gesetzt ist, kann immer noch jede*r einen Account registrieren, diesen aber zunächst nur dafür nutzen, um Zugang zur Veranstaltung anzufragen. Admins erhalten daraufhin eine Benachrichtigungs-E-Mail und können auf der Benutzer*innen-Verwaltungs-Seite ganz unten die Anfrage annehmen oder ablehnen.</p>
    <p>Bitte beachten: sowohl diese Einstellung als auch die Benutzer*innen-Verwaltung beziehen sich auf eine konkrete Veranstaltung innerhalb einer ganzen Antragsgrün-Seite. Wenn eine andere Veranstaltung der selben Seite andere Einstellungen hat, kann die selbe Nutzer*in durchaus auf die eine Veranstaltung Zugriff haben, auf die andere aber nicht.</p>

    <p>Benutzer*innen sind standardmäßig der Gruppe &ldquo;<strong>Teilnehmer*in</strong>&rdquo; zugeordnet. Mit dieser Gruppe bekommt man Zugriff auf die Veranstaltung, aber keine besonderen Berechtigungen.</p>
    <p>Jede Benutzer*in kann einer oder mehreren Gruppen zugeordnet werden - mindestens aber einer. Neben „Teilnehmer*in” gibt es noch drei weitere Standard-Gruppen:</p>
    <ul>
        <li><strong>Seiten-Admin</strong>: Benutzer*innen in dieser Gruppe haben volle Admin-Rechte für alle Veranstaltungen dieser Seite. Dies ist die einzige Gruppe, die für alle Veranstaltungen gleichzeitig gilt (alle anderen Gruppen existieren nur innerhalb einer einzigen Veranstaltung).</li>
        <li><strong>Veranstaltungs-Admin</strong>: Benutzer*innen in dieser Gruppe haben volle Admin-Rechte für diese eine Veranstaltung.</li>
        <li><strong>Antragskommission</strong>: Benutzer*innen in dieser Gruppe können Verfahrensvorschläge bearbeiten, aber nicht die Anträge selbst.</li>
    </ul>

    <p>Neben den hier verwalteten Admin-Rollen gibt es noch eine „Super-Admin”-Rolle für Personen, die System-Updates durchführen können. Wie diese Gruppe verwaltet wird, wird in einem separaten <a href="https://github.com/CatoTH/antragsgruen/blob/main/docs/update-troubleshooting.md#my-user-account-does-not-have-administrative-privileges">technischen Dokument</a> beschrieben.</p>

    <p>Zusätzlich zu den vorgegebenen Gruppen ist es möglich, beliebig viele <strong>selbst definierte Gruppen</strong> anzulegen und jeweils beliebig viele Benutzer*innen zuzuordnen. Gründe hierfür können sein:</p>
    <ul>
        <li>Das Anlegen von Anträgen und Änderungsanträge auf eine oder mehrere Benutzer*innen-Gruppen beschränken</li>
        <li>Das Unterstützen von (Änderungs-)Anträgen beschränken</li>
        <li>Die Teilnahme an Abstimmungen über Anträge, Änderungsanträge und einfachen Fragestellungen auf eine oder mehrere Benutzer*innen-Gruppen beschränken.</li>
    </ul>

    <h3 id="layout">Layout-Anpassbarkeit</h3>
    <p>Verschiedene Aspekte des Layouts von Antragsgrün lassen sich über das Administrationsinterface anpassen – die
        meisten davon unter „Einstellungen“ → „Aussehen und Bestandteile der Seite“.</p>
    <p>Am stärksten wirkt sich die „Layout“-Einstellung aus: sie verändert das komplette Aussehen der Seite und wird
        genutzt, um eine entwickelte Anpassung an ein Corporate Design zu aktivieren. Neben dem
        „Antragsgrün-Standard“-Layout gibt es derzeit Layouts, die teils für den Deutschen Bundesjugendring, teils für
        Bündnis 90 / Die Grünen entwickelt wurden. Unter „Eigenes Farbschema anlegen” lassen sich auch Farben,
        Schriftgrößen und einige weitere Aspekte des Layouts (wie Schattierungen oder Abrundungen) an die eigene
        Corporate Identity anpassen.</p>
    <p>Weitere grundlegend andere Layouts können entweder von halbwegs
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

    <h3 id="antragsversionen">Antragsversionen</h3>
    <p>
        Ein Antrag kann in unterschiedlichen Versionen vorliegen, wenn der genaue Wortlaut im Laufe der Beratung angepasst wird.
        Dies kann beispielsweise passieren, indem <a href="#aes_uebernehmen">Änderungsanträge eingepflegt</a> werden
        oder eine Beschlussfassung erzeugt wird.
    </p>
    <p>
        Anträge haben daher immer eine interne Versionsnummer - beginnend mit Nummer 1.
        Verschiedene Versionen eines Antrags haben üblicherweise, aber nicht zwangsläufig, das selbe Antragskürzel.
        Innerhalb einer Veranstaltung muss nur die Kombination aus Antragskürzel und Versionsnummer eindeutig sein.
    </p>
    <p>
        Gibt es mehrere Versionen eines Antrags, wird bei jedem Antrag eine Übersicht der verschiedenen Versionen angezeigt.
        Benutzer*innen haben dabei die Möglichkeit, sich die inhaltlichen Änderungen zwischen den verschiedenen Versionen
        anzeigen zu lassen.
    </p>
    <p>
        Intern ist die Versionierung eines Antrags an das Feld „Überarbeitete Fassung von” in der Antrags-Administration gekoppelt.
        Dieses Feld wird automatisch befüllt, bei jeder Version ab Version 2 wird dabei der jeweilige
        Vorgänger-Antrag referenziert. Dieses Feld sollte nur händisch geändert werden, falls man explizit die
        Versionsgeschichte löschen will.
    </p>

    <h3 id="themen">Themen / Schlagworte</h3>
    <p>Anträge lassen sich auf der Startseite nicht nur streng hierarchisch in einer Tagesordnung darstellen, sondern
        auch nach nach vorgegebenen Schlagworten gliedern. Der wichtigste Unterschied dabei ist, dass ein Antrag auch
        mehrere Themen / Schlagworte gleichzeitig haben kann (auf der Startseite also z.B. sowohl unter „Verkehr“ als
        auch „Umwelt“ auftauchen kann). Welche Themen zur Auswahl stehen, kann von der Leitung der Veranstaltung
        vorgegeben werden. Antragsteller*innen können beim Einreichen eines Antrags Themen auswählen.</p>
    <p>Man aktiviert diese Darstellung unter „Einstellungen“ → „Aussehen und Bestandteile der Seite“, indem man zuerst bei
        „Startseite & Tagesordnung“ den Punkt „Themen / Schlagworte als Liste“ wählt und unter
        „Einstellungen” → „Diese Veranstaltung” bei „Anträge“ die verschiedenen Themen anlegt, die zur Auswahl stehen sollen.
        Im allgemeinen wird auch die Einstellung „Mehrere Themen pro Antrag möglich“ gleich darunter empfehlenswert sein.</p>

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
    <p>Antragsgrün unterstützt mehrere Stufen der sprachlichen Anpassung:</p>
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
        <li>
            Einige sprachliche Anpassungen können auch pro Antragstyp festgelegt werden - beispielsweise die Einleitung der
            Bestätigungsmail beim Einreichen von Anträgen oder Bewerbungen, oder die einleitende Erklärung beim Einreichen.
            Diese kann man beim jeweilgen Antragstyp unter „Antragstyp-spezifische Texte / Übersetzungen” eingeben.
        </li>
    </ul>

</div>
