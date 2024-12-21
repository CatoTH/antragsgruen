<?php

use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün: Antragsstatus-Referenz';
/** @var \app\controllers\Base $controller */
$controller = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://antragsgruen.de/help/status';
$controller->layoutParams->alternateLanuages = ['en' => 'https://motion.tools/help/status'];
$controller->layoutParams->addBreadcrumb('Start', '/');
$controller->layoutParams->addBreadcrumb('Hilfe', '/help');
$controller->layoutParams->addBreadcrumb('Status');
$controller->layoutParams->fullWidth = true;

?>
<h1>Referenz: Antragsstatus</h1>

<div class="content managerHelpPage">

    <p><a href="/help"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Zurück zur Hilfe</a></p>

    <h2>Referenz: Antragsstatus</h2>

    <table class="statusReferenceTable">
        <colgroup>
            <col class="name">
            <col class="visibility">
            <col class="description">
        </colgroup>
        <thead>
        <tr>
            <th>Name</th>
            <th>Sichtbar</th>
            <th>Beschreibung</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <th>Entwurf</th>
            <td>Nein</td>
            <td>Ein Mitglied hat angefangen, einen Antrag einzureichen, diesen aber (noch) nicht eingereicht. Möglicherweise aus Versehen, möglicherweise beabsichtigt.</td>
        </tr>
        <tr>
            <th>Entwurf (Admin)</th>
            <td>Nein</td>
            <td>Dieser Status kann vom Admin gesetzt werden, um einen Antrag unsichtbar zu machen, ohne dass dieser auf einer To-Do-Liste auftaucht.</td>
        </tr>
        <tr>
            <th>Eingereicht (ungeprüft)</th>
            <td>Nein <sup>[1]</sup></td>
            <td>Dieser Status ist vor allem relevant, falls die Freischaltung von (Änderungs-)Anträgen aktiv wird. In diesem Fall erscheinen eingereichte Anträge in diesem Status, um vom Admin vor der Veröffentlichung geprüft zu werden.</td>
        </tr>
        <tr>
            <th>Eingereicht (geprüft, unveröffentlicht)</th>
            <td>Nein <sup>[1]</sup></td>
            <td>Kann vom Admin gesetzt werden, um zu markieren, dass der Antrag zwar bereits geprüft wurde, aber dennoch noch nicht sichtbar sein soll.</td>
        </tr>
        <tr>
            <th>Eingereicht</th>
            <td>Ja</td>
            <td>Sichtbar, freigeschaltet. Dies ist der Standard-Status für alle veröffentlichten Anträge.</td>
        </tr>
        <tr>
            <th>Beschluss (vorläufig)</th>
            <td>Ja</td>
            <td>Ein vorläufiger Beschluss. Funktional besteht kein Unterschied zum regulären Beschluss, außer dass hierurch angezeigt werden kann, dass ggf. noch eine redaktionelle Nachprüfung erfolgen kann.</td>
        </tr>
        <tr>
            <th>Beschluss</th>
            <td>Ja</td>
            <td>Ein engültiger Beschluss.</td>
        </tr>
        <tr>
            <th>Zurückgezogen</th>
            <td>Ja</td>
            <td>Der Antrag wurde vom Mitglied zurückgezogen.</td>
        </tr>
        <tr>
            <th>Zurückgezogen (unsichtbar)</th>
            <td>Nein</td>
            <td>Der Antrag wurde vom Mitglied zurückgezogen und ist nicht mehr sichtbar.</td>
        </tr>
        <tr>
            <th>Erledigt durch anderen Antrag</th>
            <td>Ja <sup>[2]</sup></td>
            <td>Der (Änderungs-)Antrag wurde durch einen anderen Antrag hinfällig. Der ersetzende Antrag kann verlinkt werden.</td>
        </tr>
        <tr>
            <th>Erledigt durch anderen ÄA</th>
            <td>Ja <sup>[2]</sup></td>
            <td>Der (Änderungs-)Antrag wurde durch einen anderen Änderungsantrag hinfällig. Der ersetzende Änderungsantrag kann verlinkt werden.</td>
        </tr>
        <tr>
            <th>Abstimmung</th>
            <td>Ja</td>
            <td>Über den (Änderungs-)Antrag soll abgestimmt werden.</td>
        </tr>
        <tr>
            <th>Angenommen</th>
            <td>Ja</td>
            <td>Über den (Änderungs-)Antrag wurde abgestimmt und die nötige Mehrheit wurde erreicht. Wird Automatisch beim Schließen einer Abstimmung gesetzt.</td>
        </tr>
        <tr>
            <th>Abgelehnt</th>
            <td>Ja</td>
            <td>Über den (Änderungs-)Antrag wurde abgestimmt und die nötige Mehrheit wurde NICHT erreicht. Wird Automatisch beim Schließen einer Abstimmung gesetzt.</td>
        </tr>
        <tr>
            <th>Quorum verfehlt</th>
            <td>Ja</td>
            <td>Über den (Änderungs-)Antrag wurde abgestimmt und das nötige Quorum wurde NICHT erreicht. Wird Automatisch beim Schließen einer Abstimmung gesetzt.</td>
        </tr>
        <tr>
            <th>Quorum erreicht</th>
            <td>Ja</td>
            <td>Über den (Änderungs-)Antrag wurde abgestimmt und das nötige Quorum wurde erreicht. Wird Automatisch beim Schließen einer Abstimmung gesetzt.</td>
        </tr>
        <tr>
            <th>Modifiziert übernommen</th>
            <td>Ja</td>
            <td>Als Status ohne besondere Bedeutung; wichtig als Verfahrensvorschlag.</td>
        </tr>
        <tr>
            <th>Modifiziert</th>
            <td>Ja</td>
            <td><em>Rein informativer Status ohne besondere Funktion</em></td>
        </tr>
        <tr>
            <th>Übernahme</th>
            <td>Ja</td>
            <td><em>Rein informativer Status ohne besondere Funktion</em></td>
        </tr>
        <tr>
            <th>Pausiert</th>
            <td>Ja</td>
            <td><em>Rein informativer Status ohne besondere Funktion</em></td>
        </tr>
        <tr>
            <th>Erledigt</th>
            <td>Ja</td>
            <td><em>Rein informativer Status ohne besondere Funktion</em></td>
        </tr>
        <tr>
            <th>Informationen fehlen</th>
            <td>Ja</td>
            <td><em>Rein informativer Status ohne besondere Funktion</em></td>
        </tr>
        <tr>
            <th>Nicht zugelassen</th>
            <td>Ja</td>
            <td><em>Rein informativer Status ohne besondere Funktion</em></td>
        </tr>
        <tr>
            <th>Behandelt</th>
            <td>Ja</td>
            <td><em>Rein informativer Status</em></td>
        </tr>
        <tr>
            <th>Antwort</th>
            <td>Ja</td>
            <td><em>Rein informativer Status</em></td>
        </tr>
        <tr>
            <th>Verschoben</th>
            <td>Ja</td>
            <td>Ein Platzhalter-Antrag an der ursprünglichen Stelle, um die Verschiebung an eine andere Stelle (anderer Tagesordnungspunkt oder Veranstaltung) transparent zu machen.</td>
        </tr>
        <tr>
            <th>Vorgeschlagene Verschiebung von anderem Antrag</th>
            <td>Nein</td>
            <td>Interner Status, um die vorgeschlagene Verschiebung eines Änderungsantrags zu einem anderen Antrag zu markieren.</td>
        </tr>
        <tr>
            <th>Sonstiger Status</th>
            <td>Ja</td>
            <td>Platzhalter-Status. Hier kann ein beliebiger Text hinterlegt werden, um einen informativen Status zu vergeben, der anderweitig nicht vorgesehen ist.</td>
        </tr>
        </tbody>
    </table>

    <p>
    <sup>[1]</sup> Standardmäßig nicht sichtbar. Es ist aber einstellbar, dass diese (ausgegraut) anzeigt werden. Siehe: Veranstaltungs-Einstellungen -> Anträge<br>
    </p>
    <p>
    <sup>[2]</sup> Standardmäßig sichtbar. Kann über die Datenbank händisch unsichtbar gestellt werden.
    </p>
</div>

