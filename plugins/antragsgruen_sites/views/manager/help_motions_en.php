<?php

use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün: Submitting motions';
/** @var \app\controllers\Base $controller */
$controller = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://motion.tools/help/member-motion';
$controller->layoutParams->alternateLanuages = ['de' => 'https://antragsgruen.de/help/member-motion'];
$controller->layoutParams->addBreadcrumb('Home', '/');
$controller->layoutParams->addBreadcrumb('Help', '/help');
$controller->layoutParams->addBreadcrumb('Motions');

?>
<h1>Members submitting motions</h1>

<div class="content managerHelpPage">

    <p><a href="/help"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Back to the main help</a></p>
    <ul class="tocFlat">
        <li>
            <a href="#introduction" onClick="$('#introduction').scrollintoview({top_offset: -30}); return false;">Introduction</a>
        </li>
        <li>
            <a href="#permissions" onClick="$('#permissions').scrollintoview({top_offset: -30}); return false;">Permissions & Publication</a>
        </li>
        <li>
            <a href="#deadlines" onClick="$('#deadlines').scrollintoview({top_offset: -30}); return false;">Submission Deadlines</a>
        </li>
        <li>
            <a href="#comments" onClick="$('#comments').scrollintoview({top_offset: -30}); return false;">Comments</a>
        </li>
        <li>
            <a href="#likes" onClick="$('#likes').scrollintoview({top_offset: -30}); return false;">Likes</a>
        </li>
        <li>
            <a href="#support" onClick="$('#support').scrollintoview({top_offset: -30}); return false;">Collecting supporters before submission</a>
        </li>
    </ul>

    <h2 id="introduction">Introduction</h2>

    <p>It is fairly straightforward to allow the members of your organization (or the general public) to submit motions or resolutions using Antragsgrün. This could be in preparation for a general meeting of an association, part of a public online participation process or similar other consultations. When installing Antragsgrün for the first time, a setup assistant will ask some questions regarding your usage scenario. Once you’re done with it, you are good to go. However, beyond those baseline questions, you can still configure many more details if necessary. The following page explains some important ones.</p>

    <p>For this tutorial, we assume that the initial setup of Antragsgrün is completed and there already is a motion type called “Motion”. If the latter is missing, you can create it by going to “Create new motion type” and then choosing the “Standard template: Motion”.</p>

    <p>A preface about the Terminology: Mind that we are not one hundred percent consistent in when we are using the term “Motion” or “Resolution”, as we have seen different organizations using Antragsgrün using different terms (we have also seen organizations calling it “Petition”, “Draft Resolution” or “Proposal”). For the purpose of this help section and as a default, we will call documents submitted by members that have not yet been decided upon “Motions”, and once it has been adopted a “Resolution”. However, being aware that whatever terminology we choose, there is a good chance it’d be not the same one your organization is using: all terms used in the frontend of Antragsgrün can be adjusted by you as an administrator, to align the wording with your statutes.</p>

    <h2 id="permissions">Permissions & Publication</h2>


    <p>There are two major decisions to make regarding the conditions for publishing a motion: 1) Do users need to create a login to bring forward a motion (and, for that matter, verify their e-mail address), or should that not be necessary? And 2) Once a user submits a motion, should they be visible to other users right away, or should there rather be a reviewing process by admins checking each submission for admissibility?</p>

    <p>What the best decision is depends a lot on the scenario in which the system will be used in. We can give some hints and considerations, though:</p>
    <ul>
        <li>Requiring members to log in before submitting a motion does set a barrier. Not a huge one for most, but a barrier nonetheless, as for first-time users it requires users to choose a password, go to their e-mail program and confirm a registration e-mail. In small and informal settings with a decent amount of trust, this barrier might not be worth it.</li>
        <li>If you skip requiring a login, it is advisable, though, to have an admin review the submissions before publishing them. Skipping a login, after all, means that technically really everyone can submit content, including potential spam-bots.</li>
        <li>If users submit motions without logging in, they can only submit them - nothing else. It will not be possible for users to withdraw a submitted motion, or modify its content (only admins will be able to do that then).</li>
    </ul>

    <p>You can choose whether to require a login in the Settings at “Edit motion types” -> “Motions” -> “Permissions” -> “Submit Motions”. The following options are available:</p>
    <figure class="helpFigure center">
        <img src="/img/help/Permissions.png" alt="Screenshot of the permission settings">
    </figure>
    <ul>
        <li>“Registered Users” requires users to create an account and log in before submitting a motion.</li>
        <li>“Everyone”, on the other side, will not require a login. (Though users may still choose to do so)</li>
        <li>“Admins” restricts the submissions of motions to admins. This is mainly relevant for scenarios where a draft document is to be presented to members, potentially giving them the possibility to comment or amend them. This process will be explained more in detail in [we’re working on it :)]</li>
        <li>“Nobody”: nobody can submit motions - through the regular way. Mind that admins will still be able to do so through the backend document list. The difference between “Admins” and “Nobody” is therefore rather marginal in this case.</li>
        <li>“Selected groups”: larger organizations sometimes want to restrict the possibility of submitting motions to a subset of their members (e.g. delegates), while still giving all others read access. To achieve this, you can define user groups in the settings at “Registered users” -> “Groups” and then choose one or multiple of these groups when setting the permissions for the motion type. (Mind that if you want to restrict access to the whole site to specific users, including read access, you can do so under “This consultation” -> “Access to this consultation”.)</li>
    </ul>

    <p>To choose if admins need to review motions before they become visible, refer to the setting “Admins need to review motions before publication” under “This consultation” -> “Motions”. For amendments, the same can be set up independently, one section below at “Amendments”. If this is active, admins will receive a notification e-mail whenever a motion to review is submitted. You can specify who receives that e-mail at “This consultation” -> “E-Mails” -> “Admins”. In case multiple admins are to be notified, you can enter multiple e-mail addresses, separating them by a comma (for example “test1@example.org, test2@example.org").</p>

    <figure class="helpFigure right bordered">
        <img src="/img/help/Reviewing.png" alt="Screenshot of the reviewing process">
    </figure>

    <p>Admins can then review and publish a motion in its respective admin view. The link to that is in the e-mail, but it is also possible to get there either by the then-visible “To Do” link in the menu or through the admin’s motion list. On the top of this page will be a prominently placed “Publish” button. Clicking it performs the following actions:</p>
    <ul>
        <li>The status of the motion will be changed from “Submitted (unpublished)” to “Published”.</li>
        <li>An automatically generated signature will be assigned.</li>
        <li>In case the checkbox “Send a confirmation e-mail to the proposer of a motion when it is published” in “This consultation” -> “E-Mails” has been selected, a notification will be sent.</li>
    </ul>

    <p>Some hints regarding this:</p>
    <ul>
        <li>If you want to assign a different signature than the automatically generated one, you can either just change it before or after using the “Publish” button, or just skip the button entirely and set both the signature and the new status by hand.</li>
        <li>The signature needs to be unique and must not be empty. If you do not want the signature to be visible to users, it is possible to suppress it at “Appearance and components of this site” -> “Hide title signatures”</li>
        <li>It is possible to un-publish motions afterward. To do so, you can change the status back to “Submitted (unpublished)”.</li>
        <li>If you have reviewed a motion but do not yet want to publish it (e.g. to publish all submitted motions at the very same time), you can set the status to “Submitted (reviewed, not yet published)”.</li>
    </ul>

    <h2 id="deadlines">Submission Deadlines</h2>

    <p>In many cases, there is a fixed time frame in which members can submit motions. To enforce this with Antragsgrün, you can set a specific deadline for each motion type, in the settings at “Motions” -> “Deadlines”. If the field is empty, there is no deadline, submission will always be possible. It is also possible to set separate deadlines for motions and amendments.</p>

    <p>However, there might be scenarios, where there are multiple deadlines. For example, some organizations set the deadline for regular motions to be x days before the general assembly, but still want members to be able to submit motions for urgent resolutions. There are two general approaches to how to handle that with Antragsgrün. One would be to consider such urgent resolutions to be absolute exceptions that involve admin intervention: as admins always can create motions, independent of the deadline, members would talk to admins to have them submit these urgent resolutions on their behalf. The second approach would be to explicitly support them through the system by creating a separate motion type called “Urgent resolution” (or similar). As each motion type can have its own deadline, the regular motion type would have a dead line set, while the new one would not. The urgent resolution type would typically not have the option “Call to create as big, colored button” set, as to promote this option less prominently.</p>

    <p>Using deadlines, it is only possible to set the end of the submission time frame, and for most use cases, this will be enough. In case it is necessary to set a start time for submission, there are again two ways to achieve that, one manual way and one using dedicated functionality. The manual way to achieve this would be to initially set the submission policy to “Nobody” (or “Admins”), and at the given time change it to “Everyone” / “Registered Users”. The more sophisticated way would be to enable the “Complex schedule / phases” functionality in the Deadline section of the motion type. Using a complex schedule, it is possible to define one or multiple time frames for different actions in the system, including the submission of motions. This enables some more complex workflows, but is, as the name suggests, more complex. From our experience, in most cases, it is more advisable to keep things simple.</p>

    <h2 id="comments">Comments</h2>

    <p>Users can comment on motions (and amendments) - if this is set up by the administrators. The installation assistant already asks if this should be enabled or not. After the initial setup, this can be changed at the motion type settings.</p>

    <p>Enabling or disabling comments is done at the same place as setting the permissions for submitting motions - that is, at “Permissions” -> “Comments”. To deactivate it, select “Nobody”, to activate it, “Registered users” or “Everyone”. (The options “Admins” and “Selected groups” do exist here, too, but hardly ever relevant. There is also an option to require each comment being screened before publication, though this is not a frequently used option.)</p>

    <figure class="helpFigure right bordered">
        <img src="/img/help/Comments.png" alt="Screenshot of paragraph-based comments">
    </figure>

    <p>By default, comments to a motion are shown below the motion text. Comments refer to the motion as a whole. If you would prefer comments to refer to specific paragraphs, you can set that up by choosing “Comments: Paragraph-based” in the motion type settings at “Motion Sections” (bottom) -> “Motion text”. Once selected, each paragraph of the motion will have its own comment section; comments are not visible by default (to avoid interrupting the reading flow of the motion text), but the number of comments is visible as an annotation next to each paragraph, including the option to show the comments.</p>

    <h2 id="likes">Likes</h2>

    <p>It is possible to allow users to express their agreement (or disagreement) with a specific motion, to get an initial overview on controversial topics before a meeting. This function is not enabled by default and has to be enabled for a particular motion type by following these steps:</p>
    <ul>
        <li>At “Permissions” -> “Supporting motions”, choose which users should be allowed to use this function.</li>
        <li>Right below this, choose “Likes”.</li>
        <li>In case an explicit “dislike” is to be offered, too, select that too.</li>
        <li>The third option, “Official support”, is not relevant in this scenario - we will come back to this option further down at “ Collecting supporters before submission”.</li>
    </ul>

    <h2 id="support">Collecting supporters before submission</h2>

    <p>Larger organizations with many members sometimes have more regulations on who may submit motions. One regulation that we encountered several times and facilitate with Antragsgrün is to require multiple eligible members to support a motion before it can officially be submitted.</p>

    <p>To facilitate this, Antragsgrün supports two approaches: either the person submitting a motion names all persons supporting it (trust-based), or the person can create the motion, but all supporting members need to explicitly express their support through the system before the initiator can formally submit it. The latter approach is more secure, but also significantly more effortful for all involved parties.</p>

    <p><strong>Simple case: The initiator names all supporting persons:</strong><br>
        To go with this variant, go to “Proposer / Supporters: Motions” in the motion type settings and select the form “Supporters given by proposer”. A new field “Supporters” will appear, where the minimum number of supporters can be entered, and whether the initiator can name more supporters than this number or not.</p>

    <p><strong>Complex case: An explicit support collection phase:</strong><br>
        If you want to go with the more secure, but also more complex variant, where each supporting person actually needs to visit the page and explicitly express their support, some more adjustments have to be made:</p>
    <ul>
        <li>At the mentioned supporter Form, choose the option “Collection phase before publication (not for organizations)”.</li>
        <li>Here, too, enter the number of required supporters.</li>
        <li>Further up on the page, under “Permissions” -> “Supporting motions”, choose at least “Registered users” (or, if applicable, specify the user eligible groups).</li>
        <li>Right next to the permission field, check the “Official support” option.</li>
        <li>Furthermore, it is advisable (though not strictly necessary), to enable “Send a confirmation e-mail to the proposer of a motion” in the consultation settings at the very bottom.</li>
    </ul>

    <figure class="helpFigure right bordered">
        <img src="/img/help/SupportCollection.png" alt="Screenshot eines absatzbasierten Kommentars">
    </figure>

    <p>Now, if a member creates a motion, a confirmation will appear that gives more instructions on how to tell the supporting members to express their support - in particular, a copy-pastable URL to the not-yet-submitted motion will be presented. Eligible members opening that page will be given the option to express their support. Once the minimum number of supports has been reached, the original proposer of the motion will receive a notification e-mail, asking them to now formally submit the motion.<</p>

</div>
