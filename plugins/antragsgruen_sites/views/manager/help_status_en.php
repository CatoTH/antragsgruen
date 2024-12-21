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
            <th>Title</th>
            <th>Visible</th>
            <th>Description</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <th>Draft</th>
            <td>No</td>
            <td>A user started the process to submit a motion, but has not actually submitted it yet. This might be intentional, or accidentally.</td>
        </tr>
        <tr>
            <th>Entwurf (Admin)</th>
            <td>No</td>
            <td>This status can be set by an admin to have a motion be invisible, yet not show up on the To-Do list.</td>
        </tr>
        <tr>
            <th>Submitted (unpublished)</th>
            <td>No <sup>[1]</sup></td>
            <td>This status is relevant if motions/amendments are being reviewed before publication. In this case, this status indicates that a motion has been submitted and is awaiting admin review.</td>
        </tr>
        <tr>
            <th>Submitted (reviewed, not yet published)</th>
            <td>No <sup>[1]</sup></td>
            <td>This status can be set by an admin to indicate that a motion/amendment has been reviewed but should not be visible yet (e.g. when all documents are to be published at the same time).</td>
        </tr>
        <tr>
            <th>Published</th>
            <td>Yes</td>
            <td>Published and visible. This is the default status once a motion or amendment has been published.</td>
        </tr>
        <tr>
            <th>Resolution (preliminary)</th>
            <td>Yes</td>
            <td>A preliminary resolution. Functionally, there is no difference to a (non-preliminary) resolution. The additional word just indicates to readers that there might still be editorial changes before the official resolution.</td>
        </tr>
        <tr>
            <th>Resolution</th>
            <td>Yes</td>
            <td>The final resolution.</td>
        </tr>
        <tr>
            <th>Withdrawn</th>
            <td>Yes</td>
            <td>This motion / amendment was withdrawn by the proposer, but is still visible.</td>
        </tr>
        <tr>
            <th>Withdrawn (invisible)</th>
            <td>No</td>
            <td>This motion / amendment was withdrawn by the proposer and is not visible anymore.</td>
        </tr>
        <tr>
            <th>Handled by another motion</th>
            <td>Yes <sup>[2]</sup></td>
            <td>This motion / amendment becomes obsolete by a different motion. The new motion can be selected in a separate dropdown.</td>
        </tr>
        <tr>
            <th>Handled by another amendment</th>
            <td>Yes <sup>[2]</sup></td>
            <td>This motion / amendment becomes obsolete by a different amendment. The new amendment can be selected in a separate dropdown</td>
        </tr>
        <tr>
            <th>Vote</th>
            <td>Yes</td>
            <td>There will be a voting if this motion / amendment will be adopted or not.</td>
        </tr>
        <tr>
            <th>Accepted</th>
            <td>Yes</td>
            <td>There was a voting and the necessary majority has been reached. This is being set automatically when closing a voting.</td>
        </tr>
        <tr>
            <th>Rejected</th>
            <td>Yes</td>
            <td>There was a voting and the necessary majority has NOT been reached. This is being set automatically when closing a voting.</td>
        </tr>
        <tr>
            <th>Quorum missed</th>
            <td>Yes</td>
            <td>There was a voting and the necessary quorum has been reached. This is being set automatically when closing a voting.</td>
        </tr>
        <tr>
            <th>Quorum reached</th>
            <td>Yes</td>
            <td>There was a voting and the necessary quorum has NOT been reached. This is being set automatically when closing a voting.</td>
        </tr>
        <tr>
            <th>Accepted (modified)</th>
            <td>Yes</td>
            <td>As status, this is purely informational status without special functionality. Relevant mostly as proposed procedure.</td>
        </tr>
        <tr>
            <th>Modified</th>
            <td>Yes</td>
            <td><em>Purely informational status without special functionality</em></td>
        </tr>
        <tr>
            <th>Adopted</th>
            <td>Yes</td>
            <td><em>Purely informational status without special functionality</em></td>
        </tr>
        <tr>
            <th>Paused</th>
            <td>Yes</td>
            <td><em>Purely informational status without special functionality</em></td>
        </tr>
        <tr>
            <th>Completed</th>
            <td>Yes</td>
            <td><em>Purely informational status without special functionality</em></td>
        </tr>
        <tr>
            <th>Missing information</th>
            <td>Yes</td>
            <td><em>Purely informational status without special functionality</em></td>
        </tr>
        <tr>
            <th>Dismissed</th>
            <td>Yes</td>
            <td><em>Purely informational status without special functionality</em></td>
        </tr>
        <tr>
            <th>Processed</th>
            <td>Yes</td>
            <td><em>Purely informational status without special functionality/em></td>
        </tr>
        <tr>
            <th>Response</th>
            <td>Yes</td>
            <td><em>Purely informational status without special functionality</em></td>
        </tr>
        <tr>
            <th>Moved</th>
            <td>Yes</td>
            <td>A placeholder motion at its original position, to set a link to a different position after this motion has been moved to a different consultation or agenda item.</td>
        </tr>
        <tr>
            <th>Proposed move from other motion</th>
            <td>No</td>
            <td>Internal status for amendments, to indicate that an amendment to a different motion is to be moved here.</td>
        </tr>
        <tr>
            <th>Custom status</th>
            <td>Yes</td>
            <td>Placeholder status. After selecting this, it is possible to enter an arbitrary status name that is not available in the list above.</td>
        </tr>
        </tbody>
    </table>

    <p>
        <sup>[1]</sup> Not visible by default. It can be set up to show these motions in an half-transparent font at: This Consultation -> Motions<br>
    </p>
    <p>
        <sup>[2]</sup> Visible by default, but can be changed by hand in the database to invisible.
    </p>
</div>

