<?php
use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün - The Online Motion Administration';
/** @var \app\controllers\Base $controller */
$controller  = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl      = 'https://motion.tools/help';
$controller->layoutParams->alternateLanuages = ['de' => 'https://antragsgruen.de/help'];
$controller->layoutParams->addBreadcrumb('Home', '/');
$controller->layoutParams->addBreadcrumb('Help');

?>
<h1>Antragsgrün / Motion.Tools<br>
    <small>The Online Motion Administration for Political Conventions and General Assemblies</small>
</h1>

<div class="content managerHelpPage">

    <h2>Antragsgrün - Manual</h2>

    <ul class="toc">
        <li>
            <strong>Instructions for specific use cases</strong>
            <ul>
                <li><a href="/help/member-motion">Allow members to submit motions</a></li>
                <li><a href="/help/amendments">Allow members to amend motions</a></li>
                <li>Tutorial coming soon: Support for applications</li>
                <li>Tutorial coming soon: Consolidate submitted amendments into a final resolution</li>
                <li><a href="/help/progress-reports">Progress reports</a></li>
                <li>Tutorial coming soon: Proposed procedures</li>
                <li>Tutorial coming soon: Votings</li>
            </ul>
        </li>
        <li>
            <a href="#basic_structure"
               onClick="$('#basic_structure').scrollintoview({top_offset: -30}); return false;">Basic structure of an Antragsgrün-Site</a>
            <ul>
                <li><a href="#motions" onClick="$('#motions').scrollintoview({top_offset: -30}); return false;">Motions / Amendments</a></li>
                <li><a href="#consultations" onClick="$('#consultations').scrollintoview({top_offset: -30}); return false;">Consultations</a></li>
                <li><a href="#motion_types" onClick="$('#motion_types').scrollintoview({top_offset: -30}); return false;">Motion Types</a></li>
                <li><a href="#section_types" onClick="$('#section_types').scrollintoview({top_offset: -30}); return false;">Section Types</a></li>
                <li><a href="#agenda" onClick="$('#agenda').scrollintoview({top_offset: -30}); return false;">Agenda</a>
                </li>
            </ul>
        </li>
        <li>
            <a href="#workflow"
               onClick="$('#workflow').scrollintoview({top_offset: -30}); return false;">Workflow: Submission,
                screening, permissions</a>
            <ul>
                <li><a href="#proposers" onClick="$('#proposers').scrollintoview({top_offset: -30}); return false;">Proposers,
                        Supporters</a></li>
                <li><a href="#screening" onClick="$('#screening').scrollintoview({top_offset: -30}); return false;">Screening
                        of motions</a></li>
                <li><a href="#login" onClick="$('#login').scrollintoview({top_offset: -30}); return false;">Login /
                        permissions</a></li>
                <li><a href="#deadlines" onClick="$('#deadlines').scrollintoview({top_offset: -30}); return false;">Deadlines</a>
                </li>
                <li><a href="#notifications"
                       onClick="$('#notifications').scrollintoview({top_offset: -30}); return false;">Notifications</a>
                </li>
            </ul>
        </li>
        <li>
            <a href="#merging"
               onClick="$('#merging').scrollintoview({top_offset: -30}); return false;">Merging amendments into a
                motion</a>
            <ul>
                <li><a href="#merging_single"
                       onClick="$('#merging_single').scrollintoview({top_offset: -30}); return false;">Merging a single
                        amendment</a></li>
                <li><a href="#merging_all" onClick="$('#merging_all').scrollintoview({top_offset: -30}); return false;">Merging
                        all amendments at once</a></li>
            </ul>
        </li>
        <li>
            <a href="#export_functions"
               onClick="$('#export_functions').scrollintoview({top_offset: -30}); return false;">Exports</a>
            <ul>
                <li><a href="#pdf" onClick="$('#pdf').scrollintoview({top_offset: -30}); return false;">PDF</a></li>
                <li><a href="#odt" onClick="$('#odt').scrollintoview({top_offset: -30}); return false;">OpenDocument /
                        Text documents</a></li>
                <li><a href="#spreadsheets"
                       onClick="$('#spreadsheets').scrollintoview({top_offset: -30}); return false;">Amendments as
                        spreadsheets</a></li>
                <li><a href="#export_misc" onClick="$('#export_misc').scrollintoview({top_offset: -30}); return false;">HTML,
                        Plain Text, RSS, further formats</a></li>
            </ul>
        </li>

        <li>
            <a href="#votings" onClick="$('#votings').scrollintoview({top_offset: -30}); return false;">Votings</a>
            <ul>
                <li><a href="#voting_limitations" onClick="$('#voting_limitations').scrollintoview({top_offset: -30}); return false;">Limitations</a></li>
                <li><a href="#voting_administration" onClick="$('#voting_administration').scrollintoview({top_offset: -30}); return false;">Administration</a></li>
                <li><a href="#voting_user" onClick="$('#voting_user').scrollintoview({top_offset: -30}); return false;">As a user</a></li>
            </ul>
        </li>

        <li>
            <a href="#advanced"
               onClick="$('#advanced').scrollintoview({top_offset: -30}); return false;">Advanced features</a>
            <ul>
                <li><a href="#user_administration" onClick="$('#user_administration').scrollintoview({top_offset: -30}); return false;">User administration</a></li>
                <li><a href="#layout" onClick="$('#layout').scrollintoview({top_offset: -30}); return false;">Adjusting
                        the layout</a></li>
                <li><a href="#line_numbering"
                       onClick="$('#line_numbering').scrollintoview({top_offset: -30}); return false;">Line
                        numbering</a></li>
                <li><a href="#editorial" onClick="$('#editorial').scrollintoview({top_offset: -30}); return false;">Editorial
                        changes</a></li>
                <li><a href="#signatures" onClick="$('#signatures').scrollintoview({top_offset: -30}); return false;">Signatures / Motion codes</a></li>
                <li><a href="#versions" onClick="$('#versions').scrollintoview({top_offset: -30}); return false;">Motion versions</a></li>
                <li><a href="#tags" onClick="$('#tags').scrollintoview({top_offset: -30}); return false;">Tags</a></li>
                <li><a href="#comments" onClick="$('#comments').scrollintoview({top_offset: -30}); return false;">Comments</a>
                </li>
                <li><a href="#liking" onClick="$('#liking').scrollintoview({top_offset: -30}); return false;">Liking /
                        Disliking motions</a></li>
                <li><a href="#translation" onClick="$('#translation').scrollintoview({top_offset: -30}); return false;">Translations
                        / Changing the wording</a></li>
            </ul>
        </li>
    </ul>

    <h2 id="basic_structure">Basic structure of an Antragsgrün-Site</h2>

    <h3 id="motions">Motions / Amendments</h3>
    <p>&ldquo;Motion&rdquo; refers to all kind of documents published on Antragsgrün. Originally, the system was primarily developed
        for assemblies of political parties (and it still is one the most wide-spread usages), therefore we still use
        this term, although a lot more kinds of documents than only motions can be submitted and published – like
        applications for elections, (drafts for) manifestos, and so on.</p>
    <p>&ldquo;Amendment&rdquo; refers to special documents that aim to alter an existing motion by specifying how the motion is
        supposed to look after applying the amendment. Antragsgrün specifically aims to ease the handling of lots of
        amendments by many means. The original motion is annotated, thus indicating which parts of it is disputed, and
        it is easy to adopt the changes into a revised version of the motion (semi-)automatically.</p>
    <p>The submission process of both motions and amendments is highly flexible and adapts to many different scenarios –
        from rather small groups, where too many formalities would overcomplicate things, to large assemblies with
        complex submission rules, possibly integrated into existing Single-Sign-On-Solutions for authentication.</p>

    <h3 id="consultations">Consultations</h3>
    <p>A consultation is a collection of all motions, drafts, applications and so on being discussed at the same time.
        It may for example correspond to an assembly or conference, to a collection of chapters of a larger manifesto or
        an election with several open posts.</p>
    <p>Each installation of Antragsgrün has at least one such consultation, but can have an arbitrary number as such.
        Therefore if a conferences takes place on a regular basis, it’s not necessary to set up a new site every single
        time or remove the content of the previous conference: a new default consultation can be created, cloning the
        preferences of the previous one, without removing the old one or invalidating existing links to motions.</p>
    <p>New consultations can be created at &ldquo;Settings&rdquo; → &ldquo;Manage more consultations on this subdomain&rdquo;. Here, you can
        also specify which one should be treated as the default consultation.</p>

    <h3 id="motion_types">Motion Types</h3>
    <p>There can be different kinds of documents published in one consultation – different both in structure or name and
        in terms of permissions, requirements or deadlines. For example, applications for an election usually need
        different input fields (name, biography, a photo) than motion (title, text, reason). Or some assemblies allow
        submitting urgency motions with another (or no) deadline than regular motions.</p>
    <p>To enable this kind of flexibility, Antragsgrün uses the concept of motion types. An arbitrary number of motion
        types can be created for every consultation, each of them having its own name, structure and permissions. Every
        motion is of exactly one motion type.</p>
    <p>The motion types can be managed in the &ldquo;Settings&rdquo; at &ldquo;Edit motion types&rdquo;.</p>

    <h3 id="section_types">Section Types</h3>

    <p>Every motion type defines a structure that each document of this type will have. In the most simple case, it would be „Title + Text“ (e.g. for resolutions), but there can be as many sections as one wants, and other formats than text are supported, too: a submitted document may contain images, embedded videos, PDFs or structured tabular data.</p>

    <p>The following types of sections are supported:</p>
    <ul>
        <li><strong>Title:</strong> a one-line text field. The title of the document (or the name of an applicant) would typically use this. Or, if used for petitions, this might contain the addressee of the submitted petition.</li>
        <li><strong>Text:</strong> A text field allowing a number of formats. Typically, one or multiple sections of this type are the „heart“ of a document, like the text of a petition or resolution, or the self-introduction of an application.</li>
        <li><strong>Text (enhanced):</strong> A text field allowing even more text formats (like centered or colored text). This comes with a price, though: text entered using the section type are not exported in all available formats, and cannot be amended.</li>
        <li><strong>Editorial text:</strong> A text field that represents more dynamic content, typically not entered by the submitter of a motion, but by an administrator or editorial person. A typical use case are progress reports, where a page will first show the decided resolution text (section type: „Text“), then the current status that may change frequently (section type: „Editorial Text“). Besides admins, sections of these type can be edited by all users either in the user group „Progress Report“ or the individual privilege „Manage editorial sections / Progress reports“, directly from within the motion view.</li>
        <li><strong>Image:</strong> Submitters of a motion or application can upload images, either in the PNG, JPEG or GIF format.</li>
        <li><strong>Tabular Data:</strong> Submitters of documents are presented with a pre-defined table that they can fill out. The table supports multiple data types: text, numbers, dates, and choices between a set of pre-defined values.</li>
        <li><strong>PDF Attachment:</strong> A PDF file can be uploaded along with the document. This document will be shown on the page as part of the document. This type could be used to upload more complex tables (like financial reports), or to upload a pre-formatted application PDF as part of an application.</li>
        <li><strong>PDF Alternative:</strong> This is a PDF-Upload, too, but with a different goal: files uploaded for this type are not shown as part of the document, but entirely replace the PDF that would otherwise be automatically generated for each submitted document. This can be used if the downloaded version of a motion or resolution is to be designed individually or follow a particular corporate identity.</li>
        <li><strong>Embedded Video:</strong> A link to a video can be entered, for example as part of an application. If the link points to Vimeo or Youtube, the video will be directly embedded.</li>
    </ul>

    <h3 id="agenda">Agenda</h3>
    <p>Setting up an agenda for a consultation is a purely optional feature of Antragsgrün and targets assemblies and
        conventions.</p>
    <p>For each agenda item, one motion type may (but does not have to) be set. Motions can be submitted for every
        agenda item with a motion type set and will appear under this very agenda item. That way, a convention may have
        one agenda item for regular motions, one for elections – which again may have several sub-items for the
        different posts to be elected. The latter ones would get the motion type &ldquo;Application&rdquo;, making it possible to
        apply specifically for, say, treasurer or chairwoman.</p>
    <p>Using the agenda feature has to be explicitly activated, either while initializing the site using the initial
        questionnaire, or afterwards by going to &ldquo;Settings&rdquo; → &ldquo;Appearance and components of this site&rdquo; and choosing one of the two
        &ldquo;Agenda&rdquo;-Styles from the &ldquo;Homepage / Agenda&rdquo; drop-down. After that, the agenda can be created on the home page of
        the consultation.</p>

    <h2 id="workflow">Workflow: Submission, screening, permissions</h2>

    <h3 id="proposers">Proposers, Supporters</h3>

    <p>Different organizations have different requirements for their members to submit motions of amendments.
        Antragsgrün tries to cover as many of those needs as possible:</p>
    <ul>
        <li>In the most simple case, submitting a motion is as easy as entering the title, the motion text and your
            name. Optionally, this can be coupled with a login process, requiring a valid username and password.
        </li>
        <li>Some organizations require a certain number of supporters for a motion or amendment. In this case, the user
            submitting the motion will be prompted to enter the names (and optionally the sub-organizations) of the
            supporting members. If the motion is submitted by a ### and not a single member, this is not necessary.
        </li>
        <li>In cases where it is vital to validate the support of every single supporter, it is possible to include a
            &ldquo;Call for supporter&rdquo; phase in the submission process. In this case, the motion is created by a user at
            first, but is not officially submitted yet. Now it’s up to the user to send the link to interested persons
            and motivate them to show their support for the motion. Once there are enough supporters, the initial
            proposer can officially submit the motion. Due to the high effort involved in this process, this workflow is
            probably only interesting for really large consultations.
        </li>
    </ul>
    <p>For each motion type, one of these models can be chosen and configured at &ldquo;Settings&rdquo; → &ldquo;Edit motion types&rdquo; →
        &ldquo;Initiators / supporters&rdquo;.</p>
    <p>For the third option, using a &ldquo;Call for supporter&rdquo; phase, some additional settings need to be set: the
        permissions for &ldquo;Supporting motions / amendments&rdquo; need to be set to &ldquo;Registered users&rdquo;, and the &ldquo;Official
        support&rdquo;-checkbox below has to be activated.</p>
    <p>In case you need additional functionality, just contact us.</p>

    <h3 id="screening">Screening of motions</h3>

    <p>In many cases, it is required by the procedure of an organization that every submitted motion of amendment is
        checked for validity by an editorial office. This is called &ldquo;screening&rdquo; and is actually recommended for cases
        where the submission form is accessible for everyone (and every spam-bot) without registration process. There
        are three variants to be chosen from:</p>
    <ul>
        <li>No screening: every submitted motion is immediately visible.</li>
        <li>Regular screening: submitted motions are only visible after they have been screened by an admin.</li>
        <li>A mixture of both: submitted motions are visible immediately, but are marked as unscreened in the
            meanwhile.
        </li>
    </ul>
    <p>The settings can be found in &ldquo;Settings&rdquo; → &ldquo;This consultation&rdquo;. The three important points are &ldquo;Screening of
        motions&rdquo;, &ldquo;Screening of amendments&rdquo; and &ldquo;Show motions publicly during the screening process&rdquo;.</p>
    <p>Please note that this can not be set on a per-motion-type-basis yet.</p>

    <h3 id="login">Login / permissions</h3>

    <p>It is possible to restrict functions like submitting motions or amendments, or supporting or commenting on them
        to registered users. Antragsgrün’s registration process is designed to support different kinds of login
        mechanisms.</p>
    <p>The most common way to register is by e-mail: new users can register an account by entering their address and a
        password and confirming a confirmation e-mail sent to that address. However, it is also possible to close user
        registration and restrict the login system to a list of known addresses. This can be done in the &ldquo;Settings&rdquo; at
        &ldquo;Login / users / admins&rdquo; by activating the &ldquo;Only allow selected users to log in&rdquo; option. Once done so, a new
        section &ldquo;User accounts&rdquo; appears, allowing to invite new users by entering their name and e-mail-address.</p>
    <p>If Antragsgrün is supposed to leverage an existing Single-Sign-On-Solution, it is possible to include other log
        in mechanisms. For example, Antragsgrün has been successfully deployed in environments providing OpenID- and
        SAML-based SSO as well as integration into CRM-systems (e.g. CiviCRM). If you are interested in that topic, please contact us.</p>

    <h3 id="deadlines">Deadlines</h3>

    <p>Antragsgrün supports setting a deadline for submitting motions and amendments. This can be done individually for
        each motion type, with separate deadlines for motions and amendments respectively. You can enter an exact time,
        and once that point of time has passed, it is not possible anymore to submit or support motions.</p>
    <p>The deadlines can be set at &ldquo;Settings&rdquo; → &ldquo;Edit motion types&rdquo; → &ldquo;Deadline&rdquo;.</p>

    <h3 id="notifications">Notifications</h3>

    <p>Antragsgrün offers many ways to stay up date on what’s happening on a consultation by e-mail-notifications.</p>
    <p>For <strong>regular participants</strong>, most of the notifications are optional. After registering on a site,
        you can go to the &ldquo;E-mail notifications&rdquo;-page via the link in the sidebar to the right. There, you can choose,
        of you want to get notifications when new motions, amendments or comments are published. By default, everyone
        gets notifications about new amendments submitted for ones own motions. Aside from that, proposers of motions
        and amendments are notified once their motions have been screened and is therefore publicly available.</p>
    <p>For <strong>administrators</strong>, it’s necessary to know when new motions and amendments have been submitted
        and need to be screened. Furthermore, notifications are sent when published motions are withdrawn or revised by
        the proposers.</p>
    <p>Aside from e-mail-notifications, public <strong>RSS-Feeds</strong> are provided about new events on a
        consultation. They can be found in the sidebar to the right of the home page.</p>

    <h2 id="merging">Merging amendments into a motion</h2>

    <p>Antragsgrün offers several ways of adopting changes requested by amendments into the corresponding motion. You
        can merge the changes of a single amendment while upholding the other amendments. Of you can merge all
        amendments at once, creating the final decided motion in one step.</p>
    <p>For both ways, it’s important to bear in mind that if two different amendments try to alter the same text
        passage, a conflict occurs that cannot be resolved by automatically. You will have to resolve it manually, which
        sometimes isn’t trivial.</p>
    <p>The basic principle for both methods is: by adopting the changes of one or many amendments, a new version of the
        motion is created, making the original one obsolete. However, the original version and the adopted amendments
        still do exist for the sake of transparency, unless explicitly deleted.</p>
    <p>
        One thing to consider when choosing between the two options is: when merging all amendments at once,
        it <em>is</em> possible to <strong>undo</strong> this operation. When merging only one amendment,
        this is <em>not</em> possible.
    </p>


    <h3 id="merging_all">Merging all amendments at once</h3>

    <p>If you want to merge all amendments at once and create the final decided version of the motion, you can go to the
        default view of the motion and choose the &ldquo;Merge amendments&rdquo;-link in the sidebar, available for administrators
        of the consultation.</p>
    <p>This way of merging amendments will present you the original motion, annotated with all proposed changes inline,
        giving you the chance to accept or reject each single change individually, as well as modifying the text
        manually. Proposed insertions of new characters, words or sentences are marked green, deletions red. If you
        accept a proposed deletion, the to be deleted text of the original motion will vanish for good, while if you
        accept an insertion, the new text given by the amendment will become permanent part of the motion. Above all,
        you can freely edit the text to include editorial changes or modified adoptions.</p>
    <p>However, in this view, conflicts between amendments may occur as well, if they propose to change the same passage
        of text in an incompatible way. Antragsgrün tries to display as many changes as possible inline, but if that’s
        not possible anymore, a collision paragraph will be inserted below the current paragraph, holding all changes
        that could not be merged into the main paragraph automatically. That way, no proposed change is getting lost,
        however the change has to be incorporated and the collision paragraph hast to be deleted manually.</p>
    <p>To reduce the number of such conflicts, you can choose before actually starting the merge which amendments to
        include into this view. Amendments that are rejected as a whole, or are changing major parts of the motion
        (global alternatives) can be excluded, greatly reducing the number of conflicts.</p>
    <p>After creating the new motion text, it is important to set the new statuses of the amendments (accepted,
        rejected, accepted modified etc.), as this cannot be determined automatically. While this does not have a
        functional impact on Antragsgrün, this is helpful for users to get a quick overview over what amendments have
        been adopted and which not.</p>

    <h4>(Public) Drafts</h4>
    <p>Merging all amendments at once can take a while, especially if there are a lot of different amendments. Therefore
        it is important that a problem with a computer does not lead to total data loss. That’s why preliminary versions
        of the merged motion are saved on a regular basis, about once a minute. If the &ldquo;Merge amendments&rdquo;-page is called
        again, before a previous editing process has been completed, you will have the choice to resume the previous
        version or start anew.<br>Attention: preliminary drafts can only be saved as long there is an internet
        connection.</p>
    <p>If merging the amendments is done publicly in the course of a live event, it is possible to grant all users
        read-only-access to the current preliminary draft of the merging process. This way, everyone gets a clear idea
        about the current state of discussion / editing. This is not enabled by default, but can be activated easily by
        the editor, by activating the &ldquo;Public&rdquo;-checkbox in the &ldquo;Draft&rdquo;-box on the bottom of the page, while being on the
        &ldquo;Merge all amendments&rdquo;-page. Once this checkbox is set, a link to a public read-only-version appears in this
        panel and at the header of the regular motion page. This public draft page can be optionally set to
        automatically update every couple of seconds to the most recent version.</p>

    <h4>Undo merging amendments</h4>
    <p>
        If a new version was created using the Merge All Amendments / Create new Version function,
        it is possible to remove the new version and make the old one including its amendments visible again
        by performing the following steps: First, either delete the new motion version or set it to an invisible status
        like "Draft (Admin)". In the latter case, it is also necessary to clear the "Replaces" field in the new version.
        Then, the original version of the motion (with the amendments) should be changed from the status "Modified" to
        "Submitted". After this, it will be visible again as the default version of this motion.
    </p>

    <h3 id="merging_single">Merging a single amendment</h3>

    <p>To merge the changes of only one amendment, you can use the function &ldquo;Adopt changes into motion&rdquo; in the sidebar
        of the regular amendment page. It is done in three steps:</p>
    <p>In the first step, you can specify the signature of the new motion version and whether other amendments are made
        obsolete by this adoption. The latter is highly important, as amendments made obsolete will not lead to
        conflicts later on.</p>
    <p>In the second step, you can choose if the changes of the amendment are adopted as proposed, or in a slightly
        modified version (a modified adoption). In the latter case, you are given the chance to edit the modified
        paragraphs by hand.</p>
    <p>The last step, which can be easiest or most difficult one, deals with conflicts, that is, if the changes that
        have been specified before, are affecting text passages that are modified by other amendments that are being
        upheld. For example, if an amendment inserting a word into a sentence is adopted, but another amendment
        proposing to remove the whole sentence is being upheld, this leads to a conflict. You will be presented the
        affected paragraph of the new motion and will have to re-create the amendment based on this new version,
        maintaining the substantial intention of the original amendment. As this is a little bit tricky, it’s advisable
        to avoid this situation as much as possible, for example by not upholding amendments, marking them as global
        alternatives beforehand, or by adopting consensual amendments as early as possible, before new, potentially
        conflicting amendments can be created.</p>
    <p>By default, this function is availably only to administrators of the consultation. However, it is possible to
        make it available to the initiators of the motions in two different ways:</p>
    <ul>
        <li>In the easier case, initiators of a motion can adopt amendments to their motion, as long as those amendments
            are not in conflict with others. They can only adopt the amendments as they are, without modifying or
            rejecting them.
        </li>
        <li>In the more difficult case, the complete merging functionality as provided to administrators of the
            consultation is available to users as well. This gives users much more flexibility, but also responsibility,
            as it allows them to edit amendments of other users in case of conflicts or reject them. It also requires
            all users to understand the idea of handling merge conflicts. Therefore, this setting is only advisable in
            small, cooperative settings.
        </li>
    </ul>
    <p>If you want to activate this functionality, you can do so at &ldquo;Settings&rdquo; → &ldquo;Edit motion types&rdquo; → &ldquo;Permissions&rdquo; →
        &ldquo;May proposers of motions merge amendments themselves?&rdquo;.</p>


    <h2 id="export_functions">Exports</h2>

    <h3 id="pdf">PDF</h3>

    <p>Motions as well as amendments can be exported automatically into print-ready PDF files. To ease the handling of
        large quantities of documents at large consultations, there are not only &ldquo;one motion&rdquo;-PDFs, but also collective
        files, including all motions or amendments in one single file, and ZIP-archives for download, collecting all
        single PDFs in one big folder.</p>
    <p>Several PDF templates with different appearances are provided, covering different use cases. Which template to
        use can be chosen for each motion type individually, at Settings → Edit motion types → PDF Layout. Regular
        installations of Antragsgrün render PDFs using a rather simple PDF-generator, but for improved typography, PDF
        generation based on LaTeX is supported as well. If you need other templates than provided, please contact us for
        support.</p>

    <h3 id="odt">OpenDocument / Text documents</h3>

    <p>Motions and amendments can be exported into the OpenDocument file format (.odt), keeping all markup like bold or
        italic text intact, which makes it easy to edit the documents using standard word processing software.</p>
    <p>This export is available for administrators in the motion list (&ldquo;Motions&rdquo; at the very top).</p>

    <h3 id="spreadsheets">Amendments as spreadsheets</h3>

    <p>In some editorial meetings, a tabular overview of all submitted amendments is required to efficiently discuss all
        amendments with the proposers of the original motion. Antragsgrün is able to create such a document
        automatically in the OpenDocument Spreadsheet format, making it easy to edit it with standard software like
        OpenOffice of LibreOffice.</p>
    <p>This export is available for administrators in the motion list (&ldquo;Motions&rdquo; at the very top).</p>

    <h3 id="export_misc">HTML, Plain Text, RSS, further formats</h3>

    <p>It’s pretty easy to add further export formats. There are several already: for example, an export to plain HTML,
        to plain Text, or RSS. If you need a format that is not included yet, just contact us.</p>


    <h2 id="votings">Votings</h2>

    <p>The voting system lets users cast votes on chosen motions, amendments and questions directly on
        the Antragsgrün site. It provides a lot of flexibility in regards to the eligibility to vote,
        the publicity of votes and voting results and the kind of majority.
        It also can be used to conduct roll calls.
        The voting process is aimed to be as simple and quick for the users as possible.</p>

    <h3 id="voting_limitations">Limitations</h3>

    <p>System administrators with access to the database will always be able to see
        the votes, so no real anonymous voting system will be supported anytime soon.
        For this reason, the voting system must not be used for use cases like elections.</p>

    <h3 id="voting_administration">Administration</h3>

    <h4>Voting Blocks</h4>

    <p>A voting block is one or more motions, amendments and/or questions that are voted for at the same time with the same settings
        like majority rules or eligible users.
        They are presented to the users to be voted on as one block, either on the home page or on the page of a motion.
        In the settings of a voting block, the number of present members can be protocoled.</p>

    <p>Voting blocks can have the following statuses:</p>
    <ul>
        <li><strong>Offline</strong>: The voting will be shown as part of a proposed procedure, but the voting will be conducted outside of the Antragsgrün system, e.g. by present members raising cards.</li>
        <li><strong>Preparing</strong>: This is the status any block is in once online voting has been activated, as long as the voting has not been opened yet. Users cannot see the voting yet.</li>
        <li><strong>Opened</strong>: The voting block is visible to users and they can vote. Multiple voting blocks can be open at a time, though this might be confusing.</li>
        <li><strong>Closed</strong>: Casting new votes is not possible anymore. Once a voting is closed, those items with enough votes given the chosen majority type will be set to accepted, all others to rejected. Closed votings are visible for users on a separate page (not implemented yet).</li>
    </ul>
    <p>Note that it is possible to re-open an already closed voting by clicking &ldquo;Reset&rdquo;, which will put it back into &ldquo;Preparing&rdquo; state and delete all votes cast so far.</p>

    <p>Initially, no votings block exist. They are either created on the administration page of the votings (Settings → Votings), or on the fly while setting motions and amendments to be voted for, as will be described in the next section.</p>

    <p>More detailed settings on the visibility of a voting, the title, the assignment to a motion, the way how the majority is calculated, and a way to delete it again can be found when clicking on the settings-icon next to the title of the voting block. Most notably, there are the following settings:</p>

    <p><strong>Answers:</strong></p>
    <ul>
        <li><strong>Yes, No, Abstention</strong> (default)</li>
        <li><strong>Yes, No</strong> (no explicit abstention)</li>
        <li><strong>Present</strong> - used to perform roll calls among all registered users</li>
    </ul>

    <p><strong>Majority type:</strong></p>
    <ul>
        <li><strong>Simple majority</strong>: A motion is accepted if the number of yes-votes exceeds the number of no-votes. Abstentions are not counted.</li>
        <li><strong>Absolute majority</strong>: A motion is accepted if the number of yes-votes exceeds the number of no-votes and abstentions combined.</li>
        <li><strong>2/3-majority</strong>: A motion is accepted if the number of yes-votes is at least twice as high as the number of no-votes. Abstentions are not counted.</li>
    </ul>
    <p><strong>Publicity:</strong></p>
    <ul>
        <li><strong>Public voting results:</strong> The results can be either be public for all, or only visible for the administrators.</li>
        <li><strong>Votes cast:</strong> votes can be anonymous (which is default), visible to administrators or to all users. Users will see which option is set up before voting and this setting cannot be changed after starting a vote anymore.</li>
    </ul>
    <p><strong>Permission to vote:</strong> By default, all registered users with permission to access the consultation can vote.
        However, it is possible to use the user group system to restrict voting rights to one or more defined user groups.</p>

    <h4>Setting up a question</h4>

    <p>If you want to perform a voting over a simple question which is not directly connected to accepting a motion or amendment, simply use the &ldquo;Add a question&rdquo; button at the bottom of each voting block on the administration page. Here, you can enter the question. Users will be presented with the title of the voting block, the question and the selected options to answer.</p>
    <p>Common use cases for this are accepting an agenda (answers: yes, no, abstention) or roll calls (answers: only &ldquo;present&rdquo;).</p>

    <h4>Setting up a voting for a motion or amendment</h4>

    <p>The easiest way to add a motion or amendment to a voting block is to use the &ldquo;Add a motion or amendment&rdquo; button at the bottom of each voting block on the administration page. Here, it is also possible to add all amendments of a specific motion at the same time.</p>

    <p>Coming from the motion or amendment admin page, another option to enable voting for it is to set the status of it to &ldquo;Vote&rdquo; when editing it. If the advanced feature of &ldquo;proposed procedures&rdquo; is enabled (in the admin’s motion list, open the dropdown-menu &ldquo;Functionalities&rdquo; and choose &ldquo;Proposed procedure&rdquo; to activate it), then another option to enable voting is to choose the status &ldquo;Vote&rdquo; in the proposed procedure’s status list. In this case, the main status can remain &ldquo;Submitted&rdquo; or any other status. Once one of these statuses is chosen, additional options will appear on the admin page or the proposed procedure box. It will let you assign this motion/amendment to an existing voting block or create a new voting block.</p>

    <p>If a voting block has multiple items to vote for, the option &ldquo;<strong>Group with</strong>&rdquo; will be shown on the admin page of the motion/amendment. This lets you group the current motion/amendment with others of the same voting block. The effect will be that they will be presented as one voting item, allowing only one vote that will be counted the same for all of them. This means, the same amount of yes/no/abstention votes will be counted for all motions/amendments, and thus either all of them will be adopted or all of them will be rejected. This option can be chosen if one amendment does not make sense without the other.</p>

    <p>The &ldquo;Voting status&rdquo; should initially be &ldquo;Vote&rdquo;, indicating that no decision has be made yet. Once a voting gets closed by an admin, either &ldquo;Accepted&rdquo; or &ldquo;Rejected&rdquo; will be set automatically. However, this can also be set manually by an admin, overriding the automatic mechanism.</p>

    <p>Note the following <strong>limitations</strong>: every motion and amendment can only be part of one motion block at a time. Also, adding and removing them from voting blocks is only possible if the voting block is in &ldquo;Preparing&rdquo; mode.</p>

    <h3 id="voting_user">As a user</h3>

    <p>The voting takes place either on the home page of the consultation, or on a specific motion page - depending on how the voting block has been set up. Regular users will not see anything of the voting functionality as long as no voting is open. A voting will only become visible once the admin presses the &ldquo;Open Voting&rdquo; button at the corresponding voting block.</p>

    <p>Users can now cast one vote for each motion and amendment assigned to this voting block - either yes, no or abstention. If multiple motions/amendments are grouped by an admin using the &ldquo;Group with&rdquo; function, then they will be presented together, only vote can be cast for them and the cast vote will be valid for all of them.</p>

    <p>As long as the voting is open, users can chose to take back their vote and cast it differently. Once the voting gets closed, no changes can be made anymore and the voting will disappear from the home page. It will still be visible for users on a separate page.</p>

    <h2 id="advanced">Advanced features</h2>

    <h3 id="user_administration">User administration</h3>

    <p>The user administration of Antragsgrün can be used for several purposes:</p>
    <ul>
        <li>Adding additional administrators for the site</li>
        <li>Managing who can access the site (if this is set up)</li>
        <li>Defining user groups to grant special permissions to a user group (like to participate in a vote or create amendments)</li>
    </ul>

    <p>By default, a site can be accessed (view) by anyone and user registration is open. Two settings (under &ldquo;This consultation&rdquo; → &ldquo;Access to this consultation&rdquo;) can change this:</p>
    <ul>
        <li>&ldquo;Only logged in users are allowed to access (incl. reading)&rdquo;. Can be set to restrict the reading access to this consultation. By itself, user will still be able to register and then access. So this setting is often used in combination with the second one:</li>
        <li>&ldquo;Only allow selected users to log in&rdquo;. Once this is activated as well, administrators have full control over who can access the consultation and who no.</li>
    </ul>

    <p>If &ldquo;<strong>Only allow selected users to log in</strong>&rdquo; is chosen, users can still create own accounts, but only use them to request access for this consultation until they are added by an admin. If a user does so, admins will receive a notification e-mail and a section at the bottom of the user administration page will appear, providing a way to either accept or reject the request.</p>
    <p>Note that users might still be able to access other consultations hosted on the same site, if the settings are different there, as this permission list is administered on a per-consultation base.</p>

    <p>By default, users added to the user list have the role &ldquo;<strong>Participant</strong>&rdquo;. This means, they can access the consultation, but no special privileges comes with it.</p>
    <p>Any user can be added to one or many user groups. They need to be in at least one group to access a protected consultation, though. Besides the default &ldquo;Participant&rdquo; group, there are three pre-defined groups with special privileges:</p>
    <ul>
        <li><strong>Site Admin</strong>: users in this group have full administrational access to all consultations in this site. This is the only user group that spans multiple consultations.</li>
        <li><strong>Consultation Admin</strong>: users in this group have full administrational access to this one consultation.</li>
        <li><strong>Proposed Procedure</strong>: users in this group can edit the proposed procedure, but not the motions and amendments themselves.</li>
    </ul>

    <p>Note that beyond the user-group administered privileges, there is also a &ldquo;Super User&rdquo; eligible to perform system updates and configuration settings like changing the e-mail-server settings. This is explained in a <a href="https://github.com/CatoTH/antragsgruen/blob/main/docs/update-troubleshooting.md#my-user-account-does-not-have-administrative-privileges">technical document</a>.</p>

    <p>Besides the predefined user groups, it is possible to define an arbitrary number of <strong>custom user groups</strong> and assign any user to as many groups as desired. These custom user groups can be used for the following:</p>
    <ul>
        <li>Restricting the creation of motions and/or amendments to one or multiple custom user groups</li>
        <li>Restricting supporting of motions and/or amendments to one or multiple custom user groups</li>
        <li>Restricting the voting on motions, amendments and simple questions to one or multiple custom user groups.</li>
    </ul>

    <h3 id="layout">Adjusting the layout</h3>

    <p>Different aspects of the layout of Antragsgrün can be changed from the web interface – most of them at &ldquo;Settings&rdquo;
        → &ldquo;This consultation&rdquo; → &ldquo;Appearance&rdquo;.</p>
    <p>The &ldquo;Layout&rdquo;-setting has the biggest impact: it completely changes the design of the whole site and is used to
        activate adaptions to other corporate designs. Aside from the default layout, two themes are included that have
        been developed for the German Federal Youth Council and the German Green Party. However, using the
        &ldquo;Create custom theme&rdquo; page it is possible to change colors, font sizes and several other aspects of the layout
        according to your Corporate Identity using the web interface.</p>
    <p>If that flexibility is not enough and it is necessary to modify the theme in a more structural way, we have put
        some instructions on how to do so on our <a href="https://github.com/CatoTH/antragsgruen">Github page (&ldquo;Developing custom themes&rdquo;)</a>.</p>
    <p>For the home page of a consultation, there are several variants available (&ldquo;Homepage style&rdquo; in the settings),
        targeted towards different use cases. This setting is necessary to activate the agenda on the homepage or to
        enable the tagging feature (see further below).</p>
    <p>Aside from these two major settings, you can also modify smaller aspects of the site, like changing the logo.</p>

    <h3 id="line_numbering">Line numbering</h3>

    <p>For many organizations working with many motions, having a consistent line numbering system is vital, so we put a
        lot of effort into providing exactly that. You can set the length of a line to match your printing preferences
        (80 by default; can be changed at &ldquo;Settings&rdquo; → &ldquo;This consultation&rdquo; → &ldquo;Line length&rdquo;). The line numbers are
        reflected at many places: when displaying the motions, when exporting them into PDF and office documents, and
        also in the introduction texts in amendments (&ldquo;Insertion in line ###&rdquo;). All of this is done automatically.</p>
    <p>Normally, the line numbering starts at one for each motion. In cases when a longer manifesto is split into
        several chapters and the line numbers are supposed to be continuous throughout all chapters, this can be set at
        &ldquo;Settings&rdquo; → &ldquo;This consultation&rdquo; → &ldquo;Motions&rdquo; → &ldquo;Global line numbering throughout the whole consultation&rdquo;.</p>

    <h3 id="editorial">Editorial changes</h3>

    <p>In some specific cases, the usual way of creating an amendment does not really work well: for example, if all
        occurrences of a specific word in a long motion are supposed to be changed by another word, it would be both
        cumbersome and overwhelming in the original motion to actually change every word (and therefore annotate each
        occurrence in the original motion with this change). For such situations, we have a feature called &ldquo;editorial
        changes&rdquo;. Here, the proposed changes are written in regular text as instructions for the editorial staff of a
        consultation. Adopting these changes automatically is not possible, in this case.</p>
    <p>Editorial changes are an optional feature. They can be deactivated by the administrators of a consultation at
        &ldquo;Settings&rdquo; → &ldquo;This consultation&rdquo; → &ldquo;Amendments&rdquo; → &ldquo;Allow editorial change requests&rdquo;.</p>

    <h3 id="signatures">Signatures / Motion codes</h3>
    <p>Every published motion and amendment is assigned a unique code or signature, like &ldquo;M1&rdquo; for motion no. 1, &ldquo;AM1&rdquo;
        for amendment no. 1, or &ldquo;M1-007&rdquo; for an amendment affecting motion &ldquo;M1&rdquo; at line 7. Antragsgrün supports
        assigning those signatures manually by the administrator and automatically by different schemata.</p>
    <p>For each motion type, a character can be set, which will be the base for the signatures for the motions of this
        type - &ldquo;M&rdquo; in the example above. This way, different signatures can be created for different kinds of documents,
        like &ldquo;M&rdquo; for motions and &ldquo;A1&rdquo; for applications. The signature is assigned once the motion is published; that is,
        once the motion has been screened, of right after submitting it if the screening process is omitted. The
        signature can be changed at any time afterwards – however, it needs to be unique at any time for the whole
        consultation.</p>
    <p>For amendments, there are three different predefined patterns. At &ldquo;Settings&rdquo; → &ldquo;This consultation&rdquo; → &ldquo;Amendments&rdquo;
        → &ldquo;Numbering&rdquo; you can choose, which one of the following should be used:</p>
    <ul>
        <li>Consecutively numbering of all amendments (&ldquo;AM1&rdquo;, &ldquo;AM2&rdquo;, &ldquo;AM3&rdquo;, …)</li>
        <li>Consecutively numbering of all amendments in respect to the affected motion (&ldquo;AM1 for M1&rdquo;, &ldquo;AM2 for M1&rdquo;,
            &ldquo;AM1 for M2&rdquo;, …)
        </li>
        <li>Assigning the signatures according to the first affected line number of the motion (&ldquo;M1-23&rdquo; referring to an
            amendment that affects line 23ff. of motion M1; if a second amendment starts at the same line, it will be
            assigned &ldquo;M1-23-2&rdquo;.)
        </li>
    </ul>

    <h3 id="versions">Motion versions</h3>
    <p>
        There may be several versions of a motion if its exact textual context is modified during the amendment process.
        Motions therefore always have an internal version number, defaulting to version 1.
        Different versions of the same motion usually have the same signature, though this may be overridden by an admin.
        However, within a consultation, the combination of motion signature and version needs to be unique.
    </p>
    <p>
        If there are multiple versions of the same motion, an overview of the different versions is shown on top of
        the motion, giving the user the ability to browse through its history and view the changes made over time.
    </p>
    <p>
        Internally, the versioning of a motion is bound to the field "Replaces" in the motion administration.
        This field is being set automatically when creating new versions of a motion, starting with version 2.
        It always references the previous version of a motion. It should only be modified if it is the explicit
        wish to modify the motion history.
    </p>

    <h3 id="tags">Tags</h3>
    <p>If you don’t want to show the motions on the home page according to the strict hierarchy of an agenda, it is
        possible to use a more flexible tagging system instead. The main difference of tags is, compared to the agenda,
        that multiple tags can be assigned to each motion, instead of only one agenda item. For example, one motion can
        be assigned both the tags &ldquo;Environment&rdquo; and &ldquo;Traffic&rdquo;. The administrators of a consultation can specify the list
        of available tags. Users can then choose fitting tags when submitting a motion.</p>
    <p>The tagging system can be activated at &ldquo;Settings&rdquo; → &ldquo;Appearance and components of this site&rdquo;,
        by choosing &ldquo;Tags / categories&rdquo; at the &ldquo;Homepage / Agenda&rdquo; selection.
        The list of available tags can be specified at &ldquo;Settings&rdquo; → &ldquo;This consultation&rdquo; in the &ldquo;Motion&rdquo;-Section at
        &ldquo;Available tags&rdquo;.</p>

    <h3 id="comments">Comments</h3>
    <p>It is possible for users to comment on motions and amendments, unless the administrators of a consultation have
        deactivated this function. It can be activated and deactivated for each motion type individually, so it is, for
        example, possible to activate comments for regular motions and deactivate them for applications. Also, it is
        configurable if users need a valid login to comment, or if commenting is available publicly. These settings can
        be found at &ldquo;Settings&rdquo; → &ldquo;Edit motion types&rdquo; → &ldquo;Permissions&rdquo;. To deactivate commenting, simply choose &ldquo;Nobody&rdquo;
        at &ldquo;Comments&rdquo;.</p>
    <p>For motions, it is possible to <strong>comment single paragraphs</strong> individually. This is especially
        helpful if there are long motions, covering several aspects that might be discussed controversially. However,
        this needs to be explicitly activated by the administrator of a consultation: when editing a motion type, there
        is a list of &ldquo;Motion sections&rdquo; at the bottom. There, you can choose &ldquo;Paragraph-based&rdquo; for &ldquo;Comments&rdquo; at the
        &ldquo;Motion text&rdquo;.</p>
    <p>Optionally, a <strong>screening process</strong> can be used for comments, so new comments will have to be
        examined by an administrator before they will be published. This might be useful if no login is required before
        writing a comment. This can be activated globally for the whole consultation at &ldquo;Settings&rdquo; → &ldquo;This consultation&rdquo;
        → &ldquo;Comments&rdquo; → &ldquo;Screening of comments&rdquo;. Here, you can also choose if entering an e-mail-address is required to
        write a comment.</p>

    <h3 id="liking">Liking / Disliking motions</h3>
    <p>You can give users the chance to simply signal their approval or disapproval to a motion or amendment by putting
        themselves on a &ldquo;Like&rdquo;- / &ldquo;Dislike&rdquo;-list. These lists can be activated for each motion type at &ldquo;Settings&rdquo; →
        &ldquo;Edit motion type&rdquo; → &ldquo;Permissions&rdquo;. At &ldquo;Supporting motions&rdquo; and &ldquo;Supporting amendments&rdquo;, you can choose the
        requirements to use this function (&ldquo;Nobody&rdquo; to deactivate it altogether), and you can also decide to only allow
        Approvals / Likes, but not Disapprovals / Dislikes. (The &ldquo;Official support&rdquo;-option is not relevant for this use
        case, but is used for the &ldquo;Call for supporter&rdquo;-phase described above)</p>

    <h3 id="translation">Translations / Changing the wording</h3>
    <p>Antragsgrün supports several ways of change and internationalize the user interface and the wording:</p>
    <ul>
        <li>For each consultation, it is possible to change all strings of the user interface using the web interface.
            This can be done at &ldquo;Settings&rdquo; → &ldquo;Edit the language&rdquo;. This can be used to change a few words, change the
            e-mail-templates, etc.
        </li>
        <li>Translation Antragsgrün: It is possible to translate Antragsgrün into another language – currently, we
            provide translations into english, german and french. However, this is not possible using the web interface
            and needs some changes to the source code. If you are interested in translating Antragsgrün into a language
            not yet supported, please contact as and we’d be glad to help (especially if you were willing to contribute
            this translation into the main open-source-project).
        </li>
        <li>Somewhere in between the two ways described, there is also a way of creating language variants, like British
            English vs. American English. This also requires some additions to the source code of the project. The main
            difference of this method compared to using the web interface as described in point 1 is that translations
            created like this can be used by other consultations as well.
        </li>
        <li>
            Some changes can be made to the wording regarding specific motion types. For example, the confirmatin e-mail
            or explanation could be different when submitting a motion and an application. These specific texts can be
            entered at the respective motion type at &ldquo;Motion type specific texts / translations&rdquo;.
        </li>
    </ul>
</div>
