<?php

use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün: Robert’s Rules of Order';
/** @var \app\controllers\Base $controller */
$controller = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://motion.tools/help/roberts-rules';
$controller->layoutParams->alternateLanuages = ['de' => 'https://antragsgruen.de/help/roberts-rules'];
$controller->layoutParams->addBreadcrumb('Home', '/');
$controller->layoutParams->addBreadcrumb('Help', '/help');
$controller->layoutParams->addBreadcrumb('Robert’s Rules of Order');

?>
<h1>Using Antragsgrün with Robert’s Rules of Order</h1>

<div class="content managerHelpPage">

    <p><a href="/help"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Back to the main help</a></p>

    <h2>Overview</h2>

    <p>Robert’s Rules of Order (RRO) is the most widely used parliamentary authority for conventions, associations, and nonprofit boards. While Antragsgrün was not built for one specific rulebook, its core concepts - motions, amendments, seconding, speaking lists, and votings - map naturally onto the procedures described by Robert’s Rules. This page explains how the terminology matches and how to set up and run a meeting accordingly.</p>

    <p><em>Note: some features described on this page (the &ldquo;Currently debated&rdquo; section and secondary motions raised from the floor) are part of a new module that is currently under development and will be released as part of Antragsgrün 4.18.</em></p>

    <h2>How Robert’s Rules concepts map to Antragsgrün</h2>

    <table class="statusReferenceTable">
        <colgroup>
            <col class="name">
            <col>
        </colgroup>
        <thead>
        <tr>
            <th>Robert’s Rules of Order</th>
            <th>Antragsgrün</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Session / meeting</td>
            <td>A <strong>Consultation</strong>. Each convention or board meeting gets its own consultation; one Antragsgrün site can host many of them, e.g. one per year or per body.</td>
        </tr>
        <tr>
            <td>Order of business / agenda</td>
            <td>The <strong>Agenda</strong> on the consultation home page. Motions can be assigned to agenda items, and general debate items (&ldquo;Welcome&rdquo;, reports, elections) are simply agenda items without motions.</td>
        </tr>
        <tr>
            <td>Main motion</td>
            <td>A <strong>Motion</strong>. Motion types define who may submit them (all members, logged-in users, admins, specific user groups) and whether they are submitted in advance of the meeting or during it.</td>
        </tr>
        <tr>
            <td>Amendment / secondary amendment</td>
            <td>An <strong>Amendment</strong> to a motion; amendments to amendments are supported as well and can be enabled per motion type. Antragsgrün shows the proposed change as a diff to the original text.</td>
        </tr>
        <tr>
            <td>Second (seconding a motion)</td>
            <td>A <strong>Supporter</strong>. Configure the motion type to &ldquo;collect supporters before publication&rdquo; with a minimum of one supporter: a raised motion then only proceeds once another member has seconded it. With automatic submission enabled, no further action of the mover is needed after the second.</td>
        </tr>
        <tr>
            <td>Subsidiary, privileged and incidental motions<br>(Point of Order, Postpone, Refer to Committee, Previous Question, Recess, Adjourn, ...)</td>
            <td><strong>Secondary motions</strong>: regular motions of dedicated motion types that are hidden from the home page listings. They are raised from the floor through the &ldquo;Currently debated&rdquo; section via a simplified form; whether they need a second and whether they carry a text is configured per motion type. The kind (&ldquo;Point of Order&rdquo;, &ldquo;Motion to Recess&rdquo;, ...) is a selectable label.</td>
        </tr>
        <tr>
            <td>Obtaining the floor / recognition by the chair</td>
            <td><strong>Speaking lists</strong>. Members apply for the floor online; the chairperson grants it by starting the next slot. Speaking lists can be attached to the currently debated motion, amendment, or agenda item.</td>
        </tr>
        <tr>
            <td>Speaking for / against a motion</td>
            <td><strong>Sub-queues</strong> of a speaking list, e.g. &ldquo;In favor&rdquo; and &ldquo;Against&rdquo;, so the chair can alternate between the two sides as Robert’s Rules recommend. Other sub-queue schemes (e.g. gender quotas) are possible too.</td>
        </tr>
        <tr>
            <td>Limits on debate (length of speeches)</td>
            <td>The <strong>speaking time</strong> setting of a speaking list, including a visible countdown.</td>
        </tr>
        <tr>
            <td>Putting the question / taking a vote</td>
            <td>A <strong>Voting</strong>. Yes/No/Abstention votings on motions, amendments, or free-form questions, with configurable majority (simple majority, two-thirds, ...), configurable eligibility (user groups), and configurable visibility of the individual votes (secret ballot vs. roll call).</td>
        </tr>
        <tr>
            <td>Quorum</td>
            <td>The <strong>quorum</strong> settings of a voting, based on the number of eligible or present members.</td>
        </tr>
        <tr>
            <td>Chair / presiding officer</td>
            <td>A user with the <strong>debate moderation</strong> privilege (or a consultation admin). This privilege can be granted through a user group, so the chair does not need full administrative rights.</td>
        </tr>
        </tbody>
    </table>

    <h2>Before the meeting</h2>

    <ul>
        <li><strong>Create a consultation</strong> for the session and set up the <strong>agenda</strong> reflecting the order of business.</li>
        <li><strong>Create a motion type for main motions.</strong> Configure who may submit them and, if motions require a second, enable the supporter-collection phase with a minimum of one supporter and automatic submission once it is reached.</li>
        <li><strong>Create motion types for secondary motions</strong> you want to allow from the floor - for example one type &ldquo;Point of Order&rdquo; (no second required, no text) and one type &ldquo;Procedural motion&rdquo; (second required, optional text) covering Postpone, Recess, Previous Question and the like. Mark these types as hidden from the home page.</li>
        <li><strong>Enable the &ldquo;Currently debated&rdquo; section</strong> on the home page in the consultation’s appearance settings, and configure the speaking-list sub-queues (&ldquo;In favor&rdquo; / &ldquo;Against&rdquo;).</li>
        <li><strong>Grant the chair the debate moderation privilege</strong> via a user group, and set up the user groups of the voting members so votings can be restricted to them.</li>
    </ul>

    <h2>During the meeting</h2>

    <ul>
        <li>The chairperson <strong>selects the item under consideration</strong> - a motion, an amendment, or an agenda item. It is immediately shown to all participants in the &ldquo;Currently debated&rdquo; section of the home page.</li>
        <li>Members <strong>apply for the floor</strong> in the &ldquo;In favor&rdquo; or &ldquo;Against&rdquo; queue; the chair works through the speaking list, alternating between the sides.</li>
        <li>Members can <strong>raise secondary motions</strong> (e.g. a Point of Order or the Previous Question) directly from the widget. If the kind requires a second, it becomes pending once another member seconds it, and the chair is notified prominently.</li>
        <li>The chair can <strong>switch the debate</strong> to a pending secondary motion, attach a speaking list to it, or <strong>start a voting</strong> on it with a few clicks - and afterwards resume the interrupted main motion.</li>
        <li>When debate is closed, the chair <strong>puts the question</strong>: a voting on the amendment(s) and then on the main motion. The result determines the status (adopted / rejected), and adopted texts can be published as resolutions.</li>
    </ul>

    <h2>What Antragsgrün deliberately does not do</h2>

    <p>Some aspects of Robert’s Rules of Order are currently not supported, partly because there was not need for a technical solution yet by parties using the system, partly because it's just not implemented yet. What's not supported:</p>
    <ul>
        <li>Protocols / Writing the Minutes (Previous minutes can be published, but Antragsgrün does not provide an automated way to create them at the moment)</li>
        <li>Enforcing a special order of precedence of motions. This is left to the chair.</li>
        <li>Elections of persions.</li>
    </ul>
</div>
