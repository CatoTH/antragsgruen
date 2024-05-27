<?php

use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün: Änderungsanträge einreichen';
/** @var \app\controllers\Base $controller */
$controller = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://antragsgruen.de/help/amendments';
$controller->layoutParams->alternateLanuages = ['en' => 'https://motion.tools/help/amendments'];
$controller->layoutParams->addBreadcrumb('Start', '/');
$controller->layoutParams->addBreadcrumb('Hilfe', '/help');
$controller->layoutParams->addBreadcrumb('Änderungsanträge');

$params = \app\models\settings\AntragsgruenApp::getInstance();

?>
<h1>Änderungsanträge einreichen</h1>

<div class="content managerHelpPage">

    <p><a href="/help"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Zurück zur Hilfe</a></p>

    <h2 id="intro">Einleitung</h2>

    <p>In dieser Anleitung zeigen wir zunächst, wie es aus Sicht einer Nutzer*in aussieht, einen Antrag zu stellen. Im zweiten Teil stellen wir dann verschiedene Möglichkeiten vor, als Administrator*in diesen Vorgang an die Bedürfnisse der jeweiligen Organisation anzupassen.</p>

    <p>Wir gehen dabei davon aus, dass die Anleitung zum <a href="/help/member-motion">Anlegen eines Antrags</a> bekannt ist - viele Einstellungsmöglichkeiten, die dort vorgestellt werden, finden sich auch bei Änderungsanträgen wieder, und wir werden sie hier daher nicht mehr ganz so ausführlich besprechen.</p>

    <h2 id="userview">Änderungsanträge aus Nutzer*innen-Sicht</h2>

    <p>Änderungsanträge dienen dazu, einen konstruktiven Vorschlag zu machen, ein existierendes Dokument (wie beispielsweise einen Antrag, den Entwurf eines Parteiprogramms oder ein Positionspapier) zu verbessern. Man liefert dabei einen konkreten verbesserten Textentwurf bzw. Änderungen am Text, die im Idealfall direkt übernommen werden können.</p>

    <p>Sind Änderungsanträge möglich, kann man diese auf zwei Weisen vom Antragstext aus anlegen: entweder wählt man den Punkt „Änderungsantrag stellen“ in der Seitenleiste rechts. Oder man wählt (falls sich Änderungen nur auf einen Absatz beziehen dürfen - nur dann gibt es diese Möglichkeit), zunächst den betreffenden Absatz aus und wählt dort dann die am Rande erscheinende Bearbeiten-Funktion.</p>

    <figure class="helpFigure center">
        <img src="/img/help/AenderungsantragStellen1.png" alt="Screenshot: Änderungsantrag stellen in der Antrags-Ansicht">
    </figure>

    <p>Man bekommt nun den Originaltext vorgelegt, in einer bearbeitbaren Form. Änderungen, die man vornimmt, werden farblich gekennzeichnet: Streichungen werden rot markiert, Einfügungen grün. Ersetzt man ein Wort, sieht man daher sowohl die vorige Version (rot) als auch die vorgeschlagene Neue (grün). Anschließend gibt man seine Kontaktdaten an und schickt den Änderungsantrag ab. Je nach den Einstellungen der Veranstaltung ist der Änderungsantrag sofort sichtbar oder muss zunächst freigeschaltet werden.</p>

    <figure class="helpFigure center">
        <img src="/img/help/AenderungsantragStellen2.png" alt="Screenshot: Änderungsantrag formulieren">
    </figure>

    <p>Wenn der Änderungsantrag sichtbar ist, erscheint er in zwei Formen: zum einen wird der Änderungsantrag auf der Startseite und innerhalb des Antrags unten verlinkt. Zum anderen kann die vorgeschlagene Änderung aber auch innerhalb des eigentlichen Antragstexts im Gesamtkontext angezeigt werden. An der betreffenden Stelle (oder den betreffenden Stellen) des Originaltexts erscheint am rechten Rand ein Lesezeichen, das andeutet, dass es hier einen Änderungsantrag gibt. Klickt man darauf (oder fährt mit der Maus darüber), erscheint nun die vorgeschlagene Änderung innerhalb des Fließtexts.</p>

    <figure class="helpFigure center">
        <img src="/img/help/AenderungsantragStellen2.png" alt="Screenshot: Der Änderungsantrag innerhalb des Fließtexts des Antrags">
    </figure>

    <p>Auf diese Weise kann man als Delegierte einfach den ursprünglichen Antrag lesen, sieht aber auch auf den ersten Blick, welche Stellen gegebenenfalls umstritten ist, und welche vorgeschlagenen Änderungen es gibt.</p>

    <h2 id="setup">Einrichtung</h2>

    <p>Die Voraussetzung, um Änderungsanträge stellen zu können, ist natürlich ein änderbares Basisdokument. Dabei kann es sich entweder um einen Antrag eines anderen Mitglieds handeln, oder aber um einen Textvorschlag eines administrativen Gremiums, z.B. den Entwurf eines Wahlprogramms, das von einer Programmkommission erarbeitet wurde.</p>

    <p>Es gibt noch eine dritte Variante: bei Satzungsänderungen gibt es auch ein Basisdokument (die Satzung), das aber nicht als reguläres zu beschließendes Dokument auf der Seite erscheint - vielmehr haben hier Satzungsänderungsanträge optisch den selben Rang wie eigenständige Anträge. Hierzu wird es aber ein separates Tutorial geben.</p>

    <p>Wie Mitglieder (änderbare) Anträge einreichen können, wird in einem <a href="/help/member-motion">separaten Tutorial</a> erklärt. Werden die Basisdokumente hingegen nur von Administrierenden eingestellt (das heißt, in den Berechtigungen zum Anlegen von Anträgen ist „Niemand“ oder „Admins“ gesetzt), befindet sich die Funktion zum Anlegen von Anträgen in der Admin-Antragsliste: man geht im Menü auf Antragsliste (dieser Punkt erscheint nur, wenn man administrative Rechte hat), und wählt dann in der Leiste oben „Neu“ -> „Antrag“ (bzw. den Namen des jeweiligen Antragstyps). Hier im Admin-Bereich stehen grundsätzlich immer alle Antragstypen zur Auswahl, unabhängig von den gesetzten Berechtigungen.</p>

    <p><strong>Hinweis zu längeren Texten</strong>: häufig werden auch längere Textentwürfe auf Antragsgrün veröffentlicht - beispielsweise komplette Wahlprogramme. Es empfiehlt sich hier dringend, nicht den kompletten Text als ein einziges Dokument zu veröffentlichen, sondern unterteilt in mehrere Einzeldokumente. Beispielsweise ein Dokument pro Kapitel. Hintergrund dieser Empfehlung ist, dass der Rechenaufwand, den Antragsgrün auf dem Server erzeugt, sowohl mit der Länge des Basistexts als auch der Anzahl der Änderungsanträge pro Basistext steigt. Zehn Kapitel mit je 20 Änderungsanträgen sind viel einfacher handzuhaben als ein Dokument mit 200 Änderungsanträgen!</p>

    <p>Hinweis zu <strong>selbst zusammengestellten Antrags-Vorlagen</strong>: stellt man sich einen neuen Antragstypen mit verschiedenen Antrags-Abschnitten zusammen, ist zu beachten, dass nur Abschnitte des einfachen Typs „Text“ sowie „Titel“ änderbar sind - und auch nur dann, wenn das Häkchen bei „In Änderungsanträgen“ gesetzt ist. Angehängte PDFs oder Bilder sind beispielsweise nicht im Rahmen von Änderungsanträgen änderbar, und auch die Begründungen in Anträgen sind (sofern nicht anders eingestellt) nicht Gegenstand von Änderungsanträgen. Es lassen sich aber ohne weiteres mehrere Textfelder anlegen, die änderbar sind, oder eben das „In Änderungsanträgen“-Häkchen bei der Begründung setzen, falls diese auch geändert werden können sollen.</p>

    <h3 id="permissions">Berechtigungen</h3>

    <p>Die Berechtigungen zum Anlegen von Änderungsanträgen können auf die selbe Weise konfiguriert werden wie zum Anlegen von Anträgen. Das heißt, als Teil des Antragstypen unter „Berechtigungen“ -> „Änderungsanträge anlegen“. Eine genauere Beschreibung der Auswahlmöglichkeiten gibt es im <a href="/help/member-motion">Tutorial zum Anlegen von Anträgen</a>.</p>

    <h3 id="singleparagraph">Beschränkung auf einen Absatz</h3>

    <p>Es gibt bei Änderungsanträgen eine zusätzliche Auswahlmöglichkeit: „Änderungsanträge dürfen sich nur auf einen Absatz beziehen“. Wie der Name bereits andeutet, lässt sich hiermit steuern, ob Mitglieder in einem Änderungsantrag beliebig viele Stellen des Antrags ändern können oder nicht. Welche Variante zu bevorzugen ist hängt stark von der inhaltlichen Dynamik der jeweiligen Organisation ab:</p>
    <ul>
        <li>Nur ein Absatz darf geändert werden: diese Option wird typischerweise gewählt, wenn eher nur kleinere, lokale Änderungen gewünscht sind, bzw. Mitglieder dazu angehalten werden, für inhaltlich unterschiedliche „Änderungs-Gedanken“ auch unterschiedliche Änderungsanträge zu stellen.</li>
        <li>Beliebig viele Stellen dürfen geändert werden: ohne diese Beschränkung haben Mitglieder mehr Flexibilität darin, auch komplexere Änderungen am Text vorzuschlagen.</li>
    </ul>

    <p>Wird die Antragstellung auf einen Absatz beschränkt, gibt es noch die darüber hinaus gehende Option „..und nur auf eine konkrete Stelle“, die Änderungen auch innerhalb eines Absatzes auf eine einzige Stelle beschränkt. Diese Möglichkeit beschränkt unserer Erfahrung nach Mitglieder aber oft zu sehr, wir empfehlen sie daher nur dann, wenn ausschließlich Änderungen beispielsweise an konkreten Zahlenwerten erwünscht sind.</p>

    <p>Werden Änderungsanträge auf einen Absatz beschränkt, wird das Stellen eines Änderungsantrags für Mitglieder in einer Hinsicht noch erleichtert: es erscheinen dann (auf hinreichen großen Bildschirmen, nicht auf Smartphones) in der regulären Lese-Ansicht des Antrags rechts von jedem Absatz eine Änderungs-Link, wie in der ersten Abbildung oben auch zu sehen ist.</p>

    <h3 id="globalalternatives">Global-Alternativen, Redaktionelle Änderungsanträge</h3>

    <p>Es gibt zwei spezielle Arten von Änderungsanträgen, die aber erst explizit von den Administrierenden aktiviert werden müssen und es den Mitgliedern erlauben, etwas aus dem regulären Ablauf der Änderungsanträge auszubrechen. Beide haben den Zweck, die spätere Beschlusserstellung zu vereinfachen, falls ein Änderungsantrag sich im regulären Ablauf auf zu viele Stellen beziehen würde.</p>

    <p>Mit „Globalalternative“ können Mitglieder (oder Admins, nachträglich) Änderungsanträge markieren, die nicht nur einzelne Aspekte des Antrags ändern, sondern den Antrag gewissermaßen komplett ersetzen. In der Praxis wird ein solcher Änderungsantrag zunächst aus einer Streichung des kompletten bisherigen Texts bestehen, gefolgt von der Einfügung des neuen Texts. Bei der Eingabe der Änderung ändert sich durch dieses Häkchen zunächst noch nichts, dafür aber bei der Anzeige: eine solche Globalalternative wird nicht bei jedem betroffenen Absatz einzeln angezeigt (jeder einzelne Absatz wäre durch die Streichung betroffen), sondern nur einmal unterhalb des Antrags, als markiert als Globalalernative. Auch wenn später die Änderungsanträge eingepflegt werden, wird gar nicht erst versucht werden, einen solchen Änderungsantrag mit anderen Änderungsanträgen zusammenzuführen, da dies in der Praxis nie möglich sein wird.<br>
        Hinweis: Globalalternativen sind nicht möglich, wenn gleichzeitig die Option "Änderungsanträge dürfen sich nur auf einen Absatz beziehen“ ausgewählt ist.</p>

    <p>Redaktionelle Änderungsanträge beziehen sich auf Änderungsanträge, die sich eher auf stilistische oder strukturelle Aspekte des Basistexts beziehen. Sie werden nicht dadurch gestellt, dass das antragsteilende Mitglied jede gewünschte Änderung einzeln vornimmt, sondern in Form einer Anweisung / Bitte an die Redaktion. Ein Beispiel wäre ein Antrag, die Leser*in des Antrags lieber zu Sietzen statt zu Duzen. Würde das Mitglied hier jedes Vorkommen von „Du“ händisch ersetzen, würde der Änderungsantrag beim Lesen des Basis-Antrags an nahezu jedem Absatz erneut auftauchen, und beim Einpflegen der Änderungsanträge möglicherweise zu Konflikten mit anderen Änderungsanträgen führen. Stattdessen wird das tatsächliche Ändern, falls der Änderungsantrag übernommen werden soll, am Ende der Beschlussfassung händisch von der Redaktion vorgenommen.</p>

    <p>Beide Varianten lassen sich in den Veranstaltungs-Einstellungen (de-)aktivieren (also ausnahmsweise nicht beim Antragstypen selbst). Konkret unter „Einstellungen“ -> „Diese Veranstaltung / Programmdiskussion“ -> „Änderungsanträge“, und dann:</p>
    <ul>
        <li>„Redaktionelle Änderungsanträge zulassen“ bzw.</li>
        <li>„Globalalternativen zulassen“</li>
    </ul>

    <h3 id="supporters">Wer stellt den Änderungsantrag & Unterstützer*innen sammeln</h3>

    <p>Für Änderungsanträge gibt es die selben Möglichkeiten wie bei den Anträgen, sowohl die abgefragten Daten zur Antragstellerin anzupassen, als auch Unterstützer*innen abzufragen, ggf. durch eine explizite vorgeschaltete Unterstützungs-Phase. Hierzu wird auf das <a href="/help/member-motion">Tutorial zum Anlegen von Anträgen</a> verwiesen.</p>

    <p>Es ist allerdings zu beachten, dass die Einstellungen standardmäßig sowohl für das Stellen von Anträgen als auch von Änderungsanträgen gelten. Wird eine Unterstützungs-Phase eingestellt, gilt dies also sowohl wenn man einen Antrag stellt, als auch wenn man einen Änderungsantrag stellt. Will man hier ein unterschiedliches Prozedere, muss zunächst bei den Einstellungen für die Antragsteller*innen/Unterstützer*innen das Häkchen bei „Die selben Einstellungen auch für Änderungsanträge“ entfernt werden. Dann erscheint der selbe Einstellungsblock ein weiteres Mal, nur dass nun abweichende Einstellungen für Änderungsanträge gesetzt werden können. So ist es beispielsweise möglich, dass es zum Stellen von regulären Anträgen eine Mindestzahl an Unterstützenden geben muss, während Änderungsanträge auch von Einzelpersonen gestellt werden können.</p>
</div>
