<?php
use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 * @var \app\controllers\Base $controller
 */

$this->title = 'Antragsgrün - The Online Motion Administration';
$controller  = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl      = 'https://motion.tools/help';
$controller->layoutParams->alternateLanuages = ['de' => 'https://antragsgruen.de/help'];

/** @var \app\models\settings\AntragsgruenApp $params */
$params = \Yii::$app->params;

?>
<h1>Antragsgrün / Motion.Tools<br>
    <small>The Online Motion Administration for Associations Conventions, General Assemblies and Party Conventions.
    </small>
</h1>

<div class="content managerHelpPage">

    <h2>Antragsgrün - Manual</h2>

    <ul class="toc">
        <li>
            <a href="#basic_structure"
               onClick="$('#basic_structure').scrollintoview({top_offset: -30}); return false;">Basic structure of an
                Antragsgrün-Site</a>
            <ul>
                <li><a href="#motions" onClick="$('#motions').scrollintoview({top_offset: -30}); return false;">Motions
                        / Amendments</a></li>
                <li><a href="#consultations"
                       onClick="$('#consultations').scrollintoview({top_offset: -30}); return false;">Consultations</a>
                </li>
                <li><a href="#motion_types"
                       onClick="$('#motion_types').scrollintoview({top_offset: -30}); return false;">Motion types</a>
                </li>
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
                <li><a href="#openslides" onClick="$('#openslides').scrollintoview({top_offset: -30}); return false;">OpenSlides</a>
                </li>
                <li><a href="#export_misc" onClick="$('#export_misc').scrollintoview({top_offset: -30}); return false;">HTML,
                        Plain Text, RSS, further formats</a></li>
            </ul>
        </li>
        <li>
            <a href="#advanced"
               onClick="$('#advanced').scrollintoview({top_offset: -30}); return false;">Advanced features</a>
            <ul>
                <li><a href="#layout" onClick="$('#layout').scrollintoview({top_offset: -30}); return false;">Adjusting
                        the layout</a></li>
                <li><a href="#line_numbering"
                       onClick="$('#line_numbering').scrollintoview({top_offset: -30}); return false;">Line
                        numbering</a></li>
                <li><a href="#editorial" onClick="$('#editorial').scrollintoview({top_offset: -30}); return false;">Editorial
                        changes</a></li>
                <li><a href="#signatures" onClick="$('#signatures').scrollintoview({top_offset: -30}); return false;">Signatures
                        / Motion codes</a></li>
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
    <p>“Motion” refers to all kind of documents published on Antragsgrün. Originally, the system was primarily developed
        for assemblies of political parties (and it still is one the most wide-spread usages), therefore we still use
        this term, although a lot more kinds of documents than only motions can be submitted and published – like
        applications for elections, (drafts for) manifestos, and so on.</p>
    <p>“Amendment” refers to special documents that aim to alter an existing motion by specifying how the motion is
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
    <p>New consultations can be created at “Settings” → “Manage more consultations on this subdomain”. Here, you can
        also specify which one should be treated as the default consultation.</p>

    <h3 id="motion_types">Motion types</h3>
    <p>There can be different kinds of documents published in one consultation – different both in structure or name and
        in terms of permissions, requirements or deadlines. For example, applications for an election usually need
        different input fields (name, biography, a photo) than motion (title, text, reason). Or some assemblies allow
        submitting urgency motions with another (or no) deadline than regular motions.</p>
    <p>To enable this kind of flexibility, Antragsgrün uses the concept of motion types. An arbitrary number of motion
        types can be created for every consultation, each of them having its own name, structure and permissions. Every
        motion is of exactly one motion type.</p>
    <p>The motion types can be managed in the “Settings” at “Edit motion types”.</p>

    <h3 id="agenda">Agenda</h3>
    <p>Setting up an agenda for a consultation is a purely optional feature of Antragsgrün and targets assemblies and
        conventions.</p>
    <p>For each agenda item, one motion type may (but does not have to) be set. Motions can be submitted for every
        agenda item with a motion type set and will appear under this very agenda item. That way, a convention may have
        one agenda item for regular motions, one for elections – which again may have several sub-items for the
        different posts to be elected. The latter ones would get the motion type “Application”, making it possible to
        apply specifically for, say, treasurer or chairwoman.</p>
    <p>Using the agenda feature has to be explicitly activated, either while initializing the site using the initial
        questionnaire, or afterwards by going to “Settings” → “This consultation” and choosing one of the two
        “Agenda”-Styles from the “Homepage style” drop-down. After that, the agenda can be created on the home page of
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
            “Call for supporter” phase in the submission process. In this case, the motion is created by a user at
            first, but is not officially submitted yet. Now it’s up to the user to send the link to interested persons
            and motivate them to show their support for the motion. Once there are enough supporters, the initial
            proposer can officially submit the motion. Due to the high effort involved in this process, this workflow is
            probably only interesting for really large consultations.
        </li>
    </ul>
    <p>For each motion type, one of these models can be chosen and configured at “Settings” → “Edit motion types” →
        “Initiators / supporters”.</p>
    <p>For the third option, using a “Call for supporter” phase, some additional settings need to be set: the
        permissions for “Supporting motions / amendments” need to be set to “Registered users”, and the “Official
        support”-checkbox below has to be activated.</p>
    <p>In case you need additional functionality, just contact us.</p>

    <h3 id="screening">Screening of motions</h3>

    <p>In many cases, it is required by the procedure of an organization that every submitted motion of amendment is
        checked for validity by an editorial office. This is called “screening” and is actually recommended for cases
        where the submission form is accessible for everyone (and every spam-bot) without registration process. There
        are three variants to be chosen from:</p>
    <ul>
        <li>No screening: every submitted motion is immediately visible.</li>
        <li>Regular screening: submitted motions are only visible after they have been screened by an admin.</li>
        <li>A mixture of both: submitted motions are visible immediately, but are marked as unscreened in the
            meanwhile.
        </li>
    </ul>
    <p>The settings can be found in “Settings” → “This consultation”. The three important points are “Screening of
        motions”, “Screening of amendments” and “Show motions publicly during the screening process”.</p>
    <p>Please note that this can not be set on a per-motion-type-basis yet.</p>

    <h3 id="login">Login / permissions</h3>

    <p>It is possible to restrict functions like submitting motions or amendments, or supporting or commenting on them
        to registered users. Antragsgrün’s registration process is designed to support different kinds of login
        mechanisms.</p>
    <p>The most common way to register is by e-mail: new users can register an account by entering their address and a
        password and confirming a confirmation e-mail sent to that address. However, it is also possible to close user
        registration and restrict the login system to a list of known addresses. This can be done in the “Settings” at
        “Login / users / admins” by activating the “Only allow selected users to log in” option. Once done so, a new
        section “User accounts” appears, allowing to invite new users by entering their name and e-mail-address.</p>
    <p>If Antragsgrün is supposed to leverage an existing Single-Sign-On-Solution, it is possible to include other log
        in mechanisms. For example, Antragsgrün has been successfully deployed in environments providing OpenID- and
        SAML-based SSO. If you are interested in that topic, please contact us.</p>

    <h3 id="deadlines">Deadlines</h3>

    <p>Antragsgrün supports setting a deadline for submitting motions and amendments. This can be done individually for
        each motion type, with separate deadlines for motions and amendments respectively. You can enter an exact time,
        and once that point of time has passed, it is not possible anymore to submit or support motions.</p>
    <p>The deadlines can be set at “Settings” → “Edit motion types” → “Deadline”.</p>

    <h3 id="notifications">Notifications</h3>

    <p>Antragsgrün offers many ways to stay up date on what’s happening on a consultation by e-mail-notifications.</p>
    <p>For <strong>regular participants</strong>, most of the notifications are optional. After registering on a site,
        you can go to the “E-mail notifications”-page via the link in the sidebar to the right. There, you can choose,
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

    <h3 id="merging_single">Merging a single amendment</h3>

    <p>To merge the changes of only one amendment, you can use the function “Adopt changes into motion” in the sidebar
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
    <p>If you want to activate this functionality, you can do so at “Settings” → “Edit motion types” → “Permissions” →
        “May proposers of motions merge amendments themselves?”.</p>

    <h3 id="merging_all">Merging all amendments at once</h3>

    <p>If you want to merge all amendments at once and create the final decided version of the motion, you can go to the
        default view of the motion and choose the “Merge amendments”-link in the sidebar, available for administrators
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
        of the merged motion are saved on a regular basis, about once a minute. If the “Merge amendments”-page is called
        again, before a previous editing process has been completed, you will have the choice to resume the previous
        version or start anew.<br>Attention: preliminary drafts can only be saved as long there is an internet
        connection.</p>
    <p>If merging the amendments is done publicly in the course of a live event, it is possible to grant all users
        read-only-access to the current preliminary draft of the merging process. This way, everyone gets a clear idea
        about the current state of discussion / editing. This is not enabled by default, but can be activated easily by
        the editor, by activating the “Public”-checkbox in the “Draft”-box on the bottom of the page, while being on the
        “Merge all amendments”-page. Once this checkbox is set, a link to a public read-only-version appears in this
        panel and at the header of the regular motion page. This public draft page can be optionally set to
        automatically update every couple of seconds to the most recent version.</p>

    <h2 id="export_functions">Exports</h2>

    <h3 id="pdf">PDF</h3>

    <p>Motions as well as amendments can be exported automatically into print-ready PDF files. To ease the handling of
        large quantities of documents at large consultations, there are not only “one motion”-PDFs, but also collective
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
    <p>This export is available for administrators in the motion list (“Motions” at the very top).</p>

    <h3 id="spreadsheets">Amendments as spreadsheets</h3>

    <p>In some editorial meetings, a tabular overview of all submitted amendments is required to efficiently discuss all
        amendments with the proposers of the original motion. Antragsgrün is able to create such a document
        automatically in the OpenDocument Spreadsheet format, making it easy to edit it with standard software like
        OpenOffice of LibreOffice.</p>
    <p>This export is available for administrators in the motion list (“Motions” at the very top).</p>

    <h3 id="openslides">OpenSlides / CSV</h3>

    <p>Several organizations using Antragsgrün to prepare all motions and amendments are using OpenSlides to as a
        presentation system for their assemblies. Therefore, Antragsgrün also supports a CSV-Export that is specifically
        aimed to be imported by both major versions of OpenSlides.</p>
    <p>This export is available for administrators in the motion list (“Motions” at the very top).</p>

    <h3 id="export_misc">HTML, Plain Text, RSS, further formats</h3>

    <p>It’s pretty easy to add further export formats. There are several already: for example, an export to plain HTML,
        to plain Text, or RSS. If you need a format that is not included yet, just contact us.</p>

    <h2 id="advanced">Advanced features</h2>

    <h3 id="layout">Adjusting the layout</h3>

    <p>Different aspects of the layout of Antragsgrün can be changed from the web interface – most of them at “Settings”
        → “This consultation” → “Appearance”.</p>
    <p>The “Layout”-setting has the biggest impact: it completely changes the design of the whole site and is used to
        activate adaptions to other corporate designs. Aside from the default layout, two themes are included that have
        been developed for the German Federal Youth Council and the German Green Party. If you want to develop your own
        custom theme, we have put some instructions on how to do so on our <a
                href="https://github.com/CatoTH/antragsgruen">Github page (“Developing custom themes”)</a>.</p>
    <p>For the home page of a consultation, there are several variants available (“Homepage style” in the settings),
        targeted towards different use cases. This setting is necessary to activate the agenda on the homepage or to
        enable the tagging feature (see further below).</p>
    <p>Aside from these two major settings, you can also modify smaller aspects of the site, like changing the logo.</p>

    <h3 id="line_numbering">Line numbering</h3>

    <p>For many organizations working with many motions, having a consistent line numbering system is vital, so we put a
        lot of effort into providing exactly that. You can set the length of a line to match your printing preferences
        (80 by default; can be changed at “Settings” → “This consultation” → “Line length”). The line numbers are
        reflected at many places: when displaying the motions, when exporting them into PDF and office documents, and
        also in the introduction texts in amendments (“Insertion in line ###”). All of this is done automatically.</p>
    <p>Normally, the line numbering starts at one for each motion. In cases when a longer manifesto is split into
        several chapters and the line numbers are supposed to be continuous throughout all chapters, this can be set at
        “Settings” → “This consultation” → “Motions” → “Global line numbering throughout the whole consultation”.</p>

    <h3 id="editorial">Editorial changes</h3>

    <p>In some specific cases, the usual way of creating an amendment does not really work well: for example, if all
        occurrences of a specific word in a long motion are supposed to be changed by another word, it would be both
        cumbersome and overwhelming in the original motion to actually change every word (and therefore annotate each
        occurrence in the original motion with this change). For such situations, we have a feature called “editorial
        changes”. Here, the proposed changes are written in regular text as instructions for the editorial staff of a
        consultation. Adopting these changes automatically is not possible, in this case.</p>
    <p>Editorial changes are an optional feature. They can be deactivated by the administrators of a consultation at
        “Settings” → “This consultation” → “Amendments” → “Allow editorial change requests”.</p>

    <h3 id="signatures">Signatures / Motion codes</h3>
    <p>Every published motion and amendment is assigned a unique code or signature, like “M1” for motion no. 1, “AM1”
        for amendment no. 1, or “M1-007” for an amendment affecting motion “M1” at line 7. Antragsgrün supports
        assigning those signatures manually by the administrator and automatically by different schemata.</p>
    <p>For each motion type, a character can be set, which will be the base for the signatures for the motions of this
        type - “M” in the example above. This way, different signatures can be created for different kinds of documents,
        like “M” for motions and “A1” for applications. The signature is assigned once the motion is published; that is,
        once the motion has been screened, of right after submitting it if the screening process is omitted. The
        signature can be changed at any time afterwards – however, it needs to be unique at any time for the whole
        consultation.</p>
    <p>For amendments, there are three different predefined patterns. At “Settings” → “This consultation” → “Amendments”
        → “Numbering” you can choose, which one of the following should be used:</p>
    <ul>
        <li>Consecutively numbering of all amendments (“AM1”, “AM2”, “AM3”, …)</li>
        <li>Consecutively numbering of all amendments in respect to the affected motion (“AM1 for M1”, “AM2 for M1”,
            “AM1 for M2”, …)
        </li>
        <li>Assigning the signatures according to the first affected line number of the motion (“M1-23” referring to an
            amendment that affects line 23ff. of motion M1; if a second amendment starts at the same line, it will be
            assigned “M1-23-2”.)
        </li>
    </ul>


    <h3 id="tags">Tags</h3>
    <p>If you don’t want to show the motions on the home page according to the strict hierarchy of an agenda, it is
        possible to use a more flexible tagging system instead. The main difference of tags is, compared to the agenda,
        that multiple tags can be assigned to each motion, instead of only one agenda item. For example, one motion can
        be assigned both the tags “Environment” and “Traffic”. The administrators of a consultation can specify the list
        of available tags. Users can then choose fitting tags when submitting a motion.</p>
    <p>The tagging system can be activated at “Settings” → “This consultation”, by choosing “Tags” at the “Homepage
        style”. The list of available tags can then be set further below on the same page in the “Motion”-Section at
        “Available tags”.</p>

    <h3 id="comments">Comments</h3>
    <p>It is possible for users to comment on motions and amendments, unless the administrators of a consultation have
        deactivated this function. It can be activated and deactivated for each motion type individually, so it is, for
        example, possible to activate comments for regular motions and deactivate them for applications. Also, it is
        configurable if users need a valid login to comment, or if commenting is available publicly. These settings can
        be found at “Settings” → “Edit motion types” → “Permissions”. To deactivate commenting, simply choose “Nobody”
        at “Comments”.</p>
    <p>For motions, it is possible to <strong>comment single paragraphs</strong> individually. This is especially
        helpful if there are long motions, covering several aspects that might be discussed controversially. However,
        this needs to be explicitly activated by the administrator of a consultation: when editing a motion type, there
        is a list of “Motion sections” at the bottom. There, you can choose “Paragraph-based” for “Comments” at the
        “Motion text”.</p>
    <p>Optionally, a <strong>screening process</strong> can be used for comments, so new comments will have to be
        examined by an administrator before they will be published. This might be useful if no login is required before
        writing a comment. This can be activated globally for the whole consultation at “Settings” → “This consultation”
        → “Comments” → “Screening of comments”. Here, you can also choose if entering an e-mail-address is required to
        write a comment.</p>

    <h3 id="liking">Liking / Disliking motions</h3>
    <p>You can give users the chance to simply signal their approval or disapproval to a motion or amendment by putting
        themselves on a “Like”- / “Dislike”-list. These lists can be activated for each motion type at “Settings” →
        “Edit motion type” → “Permissions”. At “Supporting motions” and “Supporting amendments”, you can choose the
        requirements to use this function (“Nobody” to deactivate it altogether), and you can also decide to only allow
        Approvals / Likes, but not Disapprovals / Dislikes. (The “Official support”-option is not relevant for this use
        case, but is used for the “Call for supporter”-phase described above)</p>

    <h3 id="translation">Translations / Changing the wording</h3>
    <p>Antragsgrün supports three ways of change and internationalize the user interface and the wording:</p>
    <ul>
        <li>For each consultation, it is possible to change all strings of the user interface using the web interface.
            This can be done at “Settings” → “Edit the language”. This can be used to change a few words, change the
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
    </ul>
</div>