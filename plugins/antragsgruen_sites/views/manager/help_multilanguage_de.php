<?php

use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün: Anträge in mehreren Sprachen';
/** @var \app\controllers\Base $controller */
$controller = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://antragsgruen.de/help/multi-language';
$controller->layoutParams->alternateLanuages = ['en' => 'https://motion.tools/help/multi-language'];
$controller->layoutParams->addBreadcrumb('Start', '/');
$controller->layoutParams->addBreadcrumb('Hilfe', '/help');
$controller->layoutParams->addBreadcrumb('Mehrsprachigkeit');

?>
<h1>Anträge in mehreren Sprachen</h1>

<div class="content managerHelpPage">

    <p><a href="/help"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Zurück zur Hilfe</a></p>

    <h2 id="intro">Einleitung</h2>

    <p>Antragsgrün unterstützt es, Anträge und Änderungsanträge parallel in mehreren Sprachen anzulegen - beispielsweise auf Deutsch und Englisch, wenn eine Veranstaltung international besetzt ist. Jede Leser*in kann die Sprache wählen, in der sie den Antragstext sehen möchte; ist ein Text in ihrer Sprache noch nicht verfügbar, wird stattdessen eine andere vorhandene Sprachfassung mit einem entsprechenden Hinweis angezeigt.</p>

    <p>Das ist etwas anderes als die <a href="/help#uebersetzen">Übersetzung der Antragsgrün-Oberfläche selbst</a> (Menüs, Buttons, System-E-Mails usw.), die unabhängig hiervon eingerichtet werden kann. Bei der hier beschriebenen Mehrsprachigkeit geht es um den eigentlichen <strong>Inhalt</strong> der Anträge - also die Texte, die Antragsteller*innen selbst einreichen.</p>

    <h2 id="einrichtung">Einrichtung</h2>

    <p>Mehrsprachigkeit ist standardmäßig deaktiviert und muss zunächst auf Ebene der Seite (nicht der einzelnen Veranstaltung) aktiviert werden: unter „Einstellungen“ → „Mehrsprachigkeit“ lässt sich die Funktion einschalten und es können die gewünschten Sprachen ausgewählt werden. Eine der ausgewählten Sprachen ist automatisch die „primäre“ Sprache der Veranstaltung - sie ergibt sich aus der bereits an anderer Stelle eingestellten Grund-Sprache der Veranstaltung und muss nicht separat festgelegt werden.</p>

    <p>Wird nur eine Sprache ausgewählt (oder die Funktion gar nicht erst aktiviert), verhält sich Antragsgrün exakt wie bisher: keine Sprachauswahl, keine zusätzlichen Felder in der Antragstypen-Verwaltung. Bestehende, bereits eingerichtete Veranstaltungen sind von der neuen Funktion also nicht betroffen, solange sie nicht aktiv genutzt wird.</p>

    <h3 id="antragstypen">Antragstypen einrichten</h3>

    <p>Ist die Mehrsprachigkeit aktiviert, lässt sich für jeden Antrags-Abschnitt (z.B. Titel, Antragstext, Begründung) in der Antragstypen-Verwaltung zusätzlich eine Sprache festlegen. Sollen mehrere Abschnitte parallele Übersetzungen voneinander sein - z.B. ein deutscher und ein englischer Antragstext -, werden diese außerdem über ein gemeinsames „Gruppierung“-Feld (ein frei wählbarer Text, z.B. „antragstext“) miteinander verknüpft. Antragsgrün weiß dann, dass es sich bei diesen Abschnitten inhaltlich um denselben Text in verschiedenen Sprachen handelt.</p>

    <p>Werden neue Antragstypen über eine der vorgefertigten Vorlagen angelegt (z.B. „Antrag“ oder „Satzungsänderungsantrag“), erzeugt Antragsgrün bei aktivierter Mehrsprachigkeit automatisch für jede unterstützte Sprache einen eigenen, bereits korrekt gruppierten Abschnitt - inklusive einer in der jeweiligen Sprache passenden Beschriftung (z.B. „Antragstext“ vs. „Motion text“). Abschnitte, die keinen Fließtext enthalten (Bilder, PDFs, tabellarische Angaben), werden dabei bewusst nicht vervielfältigt, da ihr Inhalt in der Regel sprachunabhängig ist.</p>

    <h2 id="einreichen">Antragstellung</h2>

    <p>Reguläre Mitglieder bekommen beim Einreichen eines Antrags oder Änderungsantrags nur die Felder ihrer eigenen, aktuell eingestellten Sprache angezeigt und füllen entsprechend auch nur diese aus. Die Abschnitte der anderen Sprachen bleiben zunächst leer - es sei denn, die <a href="#automatische-uebersetzung">automatische Übersetzung</a> ist aktiviert (siehe unten).</p>

    <p>Administrator*innen sehen dagegen beim Bearbeiten eines Antrags weiterhin alle Sprachfassungen gleichzeitig und können auch fehlende Übersetzungen direkt von Hand nachtragen.</p>

    <h2 id="lesen">Lese-Ansicht und Sprachauswahl</h2>

    <p>Sobald eine Seite mehrsprachig eingerichtet ist, erscheint in der Navigationsleiste eine Sprachauswahl in Form kleiner Flaggen-Icons - je eine pro verfügbarer Sprache außer der gerade aktiven. Die gewählte Sprache wird für die Dauer des Besuchs in der Sitzung gemerkt; ohne bewusste Auswahl wird zunächst versucht, anhand der Spracheinstellung des Browsers eine passende Sprache vorzuschlagen.</p>

    <p>Ist ein Antragsabschnitt in der gewählten Sprache noch nicht vorhanden, zeigt Antragsgrün stattdessen eine andere Sprachfassung an, in der bereits Inhalt vorhanden ist - zusammen mit einem deutlich sichtbaren Hinweis, dass es sich hierbei nicht um die eigentlich gewünschte Sprache handelt. So geht nie Information verloren, nur weil eine Übersetzung noch aussteht. Das gilt auch für den Antragstitel: existiert er in der gewählten Sprache nicht, wird ebenfalls auf eine vorhandene Fassung zurückgegriffen.</p>

    <h2 id="automatische-uebersetzung">Automatische Übersetzung (optional)</h2>

    <p><strong>Die automatische Übersetzung ist eine vollständig optionale Zusatzfunktion.</strong> Ohne sie funktioniert die Mehrsprachigkeit wie oben beschrieben: fehlende Sprachfassungen bleiben leer (bzw. es wird die Fallback-Sprache mit Hinweis angezeigt), bis jemand sie von Hand nachträgt.</p>

    <p>Antragsgrün bringt dafür aber einen generischen Mechanismus mit, über den ein Plugin leere Antrags- oder Änderungsantrags-Abschnitte automatisch befüllen kann. Dieser Mechanismus ist bewusst nicht fest an das Thema „Übersetzung“ gekoppelt: technisch entscheidet ein Plugin lediglich, mit welchem Inhalt ein leerer Abschnitt gefüllt werden soll, und darf dafür beliebige andere Abschnitte des selben Antrags heranziehen. Ein Übersetzungs-Plugin nutzt das, um aus einer bereits vorhandenen Sprachfassung eine Übersetzung zu erzeugen; grundsätzlich ließe sich auf demselben Weg aber auch ein ganz anderes Plugin realisieren, das beispielsweise automatisch eine Kurzfassung des Antragstexts erstellt.</p>

    <p>Mit Antragsgrün wird ein Beispiel-Plugin ausgeliefert, das Übersetzungen über die Claude-API von Anthropic erzeugt. Da der zugrundeliegende Mechanismus aber offen gehalten ist, lässt sich grundsätzlich <strong>jeder beliebige Übersetzungsdienst</strong> (z.B. DeepL) über ein eigenes, entsprechend angepasstes Plugin anbinden - Antragsgrün selbst schreibt keinen konkreten Anbieter vor. Ist kein solches Plugin aktiviert, bleibt die automatische Übersetzung schlicht ungenutzt.</p>

    <p>Bei Änderungsanträgen berücksichtigt die automatische Übersetzung eine Besonderheit: damit die übersetzte Änderung möglichst nah an der bereits vorliegenden Übersetzung des Antragstexts bleibt (und damit der angezeigte Unterschied zwischen Antrag und Änderungsantrag in der Zielsprache dem in der Ursprungssprache entspricht), wird der bereits übersetzte Antragstext dem Übersetzungs-Plugin als Referenz mitgegeben. So werden möglichst nur die tatsächlich geänderten Passagen neu übersetzt, statt dass unveränderte Teile bei jeder Übersetzung zufällig anders formuliert werden.</p>

    <p>Automatisch erzeugte Übersetzungen werden intern entsprechend gekennzeichnet. Wird ein automatisch übersetzter Abschnitt anschließend von Hand bearbeitet, gilt er ab diesem Zeitpunkt wieder als regulärer, von einem Menschen verfasster Text.</p>

    <p>Falls Sie an der Anbindung eines eigenen Übersetzungsdienstes interessiert sind, kontaktieren Sie uns gerne.</p>
</div>
