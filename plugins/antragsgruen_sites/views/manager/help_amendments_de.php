<?php

use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün Dokumentation: Änderungsanträge einreichen';
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
    <ul class="tocFlat">
        <li>
            <a href="#userview" onClick="$('#userview').scrollintoview({top_offset: -30}); return false;">
                Änderungsanträge aus Nutzer*innen-Sicht</a>
        </li>
    </ul>

    <p><strong>Einleitung</strong></p>

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

    <p>MEHR FOLGT...</p>
</div>
