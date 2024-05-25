<?php

use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün: Sachstandsberichte';
/** @var \app\controllers\Base $controller */
$controller = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://antragsgruen.de/help/progress-reports';
$controller->layoutParams->alternateLanuages = ['en' => 'https://motion.tools/help/progress-reports'];
$controller->layoutParams->addBreadcrumb('Start', '/');
$controller->layoutParams->addBreadcrumb('Hilfe', '/help');
$controller->layoutParams->addBreadcrumb('Sachstandsberichte');

$params = \app\models\settings\AntragsgruenApp::getInstance();

?>
<h1>Sachstandsberichte</h1>

<div class="content managerHelpPage">

    <p><a href="/help"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Zurück zur Hilfe</a></p>

    <h2>Was sind Sachstandsberichte?</h2>

    <p>Wird Antragsgrün für die Archivierung von Beschlüssen genutzt, können die Beschlüsse auch um Sachstandsberichte ergänzt werden. Das heißt, dass nicht nur die gefassten Beschlüsse von allen berechtigten Mitgliedern eingesehen werden können, sondern jedem Beschluss auch ein jederzeit aktualisierbares Protokoll beigefügt werden kann, das darstellt, was seit dem Beschluss diesbezüglich geschehen ist.</p>

    <p>Der Sachstand unterscheidet sich dabei vom Beschlusstext in folgenden Punkten:</p>
    <ul>
        <li>Die Berechtigungen zum Bearbeiten des Sachstands kann unabhängig von der zum Bearbeiten des Beschlusstexts geregelt werden. Üblicherweise wird eine Änderung am Beschlusstext nur in absoluten Ausnahmefällen von ein, zwei Admins erlaubt sein, während eine Änderung am Sachstand einem weiteren Personenkreis zugänglich sein wird, z.B. dem gesamten Sekretariat oder einzelnen Arbeitsgruppen.</li>
        <li>Das Bearbeiten des Sachstands geht deutlich niederschwelliger als das des Beschlusstexts. Insbesondere ist es direkt in der regulären Antragsansicht möglich, den Sachstand zu bearbeiten, ohne zunächst in die Admin-Ansicht zu wechseln.</li>
        <li>Der Sachstand hat - unabhängig vom Beschluss an sich - ein Datum der letzten Änderung sowie eine Autor*in.</li>
    </ul>

    <p>Im folgenden wird beschrieben, wie man die Funktion Sachstandsberichte einrichtet und nutzt.</p>

    <h2>Anwendung</h2>

    <p>Im Rahmen des Beschlussfassung - also der Funktion „Änderungsanträge einpflegen“ - bekommt man die Auswahl, ob das Ergebnis ein neuer Antrag, ein vorläufiger Beschluss oder ein finaler tatsächlicher Beschluss sein soll. Wählt man den Beschluss, und ist ein passender Antragstyp angelegt (siehe „Einrichtung“), erscheint eine Auswahl, welchem Antragstypen der Beschluss zugeordnet werden soll. Hier kann man nun „Sachstandsbericht“ auswählen.</p>

    <figure class="helpFigure center">
        <img src="/img/help/Sachstandsbericht1.png" alt="Screenshot: Einen Beschluss mit Sachstandsbericht anlegen">
    </figure>

    <p>Standardmäßig erscheinen die Beschlüsse nun erst einmal wie reguläre Beschlüsse (ohne Sachstandsbericht).</p>

    <p>Berechtigte Personen bekommen aber einen zusätzlichen Abschnitt unterhalb des Beschlusstextes angezeigt, in dem sie einen Sachstand eintragen können, bzw. eventuell existierende Sachstände bearbeiten können. Sobald dies einmal geschehen ist, wird der Sachstand allen Mitgliedern angezeigt, die auch den Beschlusstext lesen können.</p>

    <figure class="helpFigure center">
        <img src="/img/help/Sachstandsbericht2.png" alt="Screenshot: Den Sachstandsbericht bearbeiten">
    </figure>

    <h2>Einrichtung</h2>

    <p>Die Einrichtung der Funktion geschieht in zwei Schritten: zum einen muss ein separater Antragstyp angelegt werden, zum anderen passende Berechtigungen zum Bearbeiten vergeben werden.</p>

    <h3>Antragstyp anlegen</h3>

    <p>Nutzt man die üblichen Antragstypen für Anträge (also einen Antragstypen mit den Abschnitten „Titel“, „Antragstext“ und ggf. „Begründung“), ist die Einrichtung für Beschlüsse mit Sachstandsberichten recht simpel: man legt einen neuen Antragstypen an, und wählt bei der Auswahl der Vorlage den Punkt „Standard-Vorlage: Sachstandsbericht“. Dabei wird ein neuer Antragstyp angelegt, der folgende Besonderheiten hat:</p>
    <ul>
        <li>Nur Admins haben die Rechte zum Anlegen, es gibt auch keine Änderungsanträge.</li>
        <li>Es gibt die Standard-Abschnitte „Titel“ und „Beschlusstext“, es gibt aber keinen Abschnitt „Begründung“ mehr (da Begründungen bei der Beschlussfassung üblicherweise entfernt werden), dafür aber einen neuen Abschnitt „Redaktionell bearbeitbarer Text“ mit dem Titel „Sachstand“.</li>
    </ul>

    <p>Benutzen die Anträge ein anderes Schema als den Standard „Titel“, „Antragstext“ und ggf. „Begründung“, muss man beim Anlegen des neuen Antragstypen für die Beschlüsse mit Sachstandsbericht darauf achten, dass dieser auch kompatibel ist. Konkret muss es für jeden nicht-optionalen Textabschnitt im ursprünglichen Antragstypen auch einen Textabschnitt im neuen Antragstypen geben. Ist dies nicht der Fall, wird der neue Antragstyp bei der Beschlussfassung später nicht auswählbar sein.</p>

    <h3>Berechtigungen vergeben</h3>

    <p>Die Berechtigung zum Bearbeiten des Sachstandsberichts hängt am Recht „Redaktionelle Antragsabschnitte / Sachstände bearbeiten“. Benutzer*innen können dieses Recht auf drei verschiedene Weisen bekommen:</p>
    <ul>
        <li>Veranstaltungs- und Seiten-Admins haben das Recht immer automatisch</li>
        <li>Benutzer*innen, die der Standard-Gruppe „Sachstände bearbeiten“ zugeteilt sind, bekommen das Recht</li>
        <li>Es ist außerdem möglich, eine neue Benutzer*innen-Gruppe anzulegen, dieser Gruppe oben genanntes Recht zuzuteilen, und dann die Berechtigten der Gruppe zuzuordnen.</li>
    </ul>

    <p>Es ist auch möglich, das Recht als „Eingeschränktes Recht“ zu vergeben - das heißt, Gruppen nur die Sachstände bestimmter Themengebiete oder Tagesordnungspunkte bearbeiten zu lassen.</p>
</div>
