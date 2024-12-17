<?php

use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün: Status Reference';
/** @var \app\controllers\Base $controller */
$controller = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://antragsgruen.de/help/status';
$controller->layoutParams->alternateLanuages = ['en' => 'https://motion.tools/help/status'];
$controller->layoutParams->addBreadcrumb('Start', '/');
$controller->layoutParams->addBreadcrumb('Help', '/help');
$controller->layoutParams->addBreadcrumb('Status');
$controller->layoutParams->fullWidth = true;

?>
<h1>Reference: Statuses</h1>

<div class="content managerHelpPage">

    <p><a href="/help"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Back to the main help</a></p>

    <h2>Reference: Statuses</h2>

    <table class="statusReferenceTable">
        <colgroup>
            <col class="name">
            <col class="visibility">
            <col class="description">
        </colgroup>
        <thead>
        <tr>
            <th>Name</th>
            <th>Visible</th>
            <th>Description</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <th>STATUS_DRAFT</th>
            <td>Nein</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_DRAFT_ADMIN</th>
            <td>Nein</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_SUBMITTED_UNSCREENED</th>
            <td>Nein <sup>[1]</sup></td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_SUBMITTED_UNSCREENED_CHECKED</th>
            <td>Nein <sup>[1]</sup></td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_SUBMITTED_SCREENED</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_RESOLUTION_PRELIMINARY</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_RESOLUTION_FINAL</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_WITHDRAWN</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_WITHDRAWN_INVISIBLE</th>
            <td>Nein</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_OBSOLETED_BY_MOTION</th>
            <td>Ja <sup>[2]</sup></td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_OBSOLETED_BY_AMENDMENT</th>
            <td>Ja <sup>[2]</sup></td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_VOTE</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_ACCEPTED</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_REJECTED</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_QUORUM_MISSED</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_QUORUM_REACHED</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_MODIFIED_ACCEPTED</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_MODIFIED</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_ADOPTED</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_PAUSED</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_COMPLETED</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_MISSING_INFORMATION</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_DISMISSED</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_PROCESSED</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_INLINE_REPLY</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_MOVED</th>
            <td>Ja</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_PROPOSED_MOVE_TO_OTHER_MOTION</th>
            <td>Nein</td>
            <td></td>
        </tr>
        <tr>
            <th>STATUS_CUSTOM_STRING</th>
            <td>Ja</td>
            <td></td>
        </tr>
        </tbody>
    </table>

    <p>
        <sup>[1]</sup> screeningMotionsShown<br>
        <sup>[2]</sup> obsoletedByMotionsShown
    </p>
</div>

