<?php

use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün: Submitting amendments';
/** @var \app\controllers\Base $controller */
$controller = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://motion.tools/help/amendments';
$controller->layoutParams->alternateLanuages = ['de' => 'https://antragsgruen.de/help/amendments'];
$controller->layoutParams->addBreadcrumb('Home', '/');
$controller->layoutParams->addBreadcrumb('Help', '/help');
$controller->layoutParams->addBreadcrumb('Amendments');

?>
<h1>Submitting amendments</h1>

<div class="content managerHelpPage">

    <p><a href="/help"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Back to the main help</a></p>

    <h2 id="introduction">Introduction</h2>

    <p>In this tutorial, we first introduce how amending a document looks from the point of view of a regular user. Then we will explain some of the most important ways that administrators can adapt the amendment flow to the requirements of their respective organizations.</p>

    <p>We do assume a familiarity with the tutorial on <a href="/help/member-motion">how to enable submission of motions</a>. Many settings to change details of the amendment submission are similar to those regarding motions and resolutions, so those that are very similar will be covered more briefly here.</p>

    <h2 id="userview">Amending as a user</h2>

    <p>Amendments are a constructive way to propose improvements to a given document (like a motion, the draft of a party platform or a draft resolution). To do so, one submits a concrete improved version of the text that, ideally, can be directly merged into the original document.</p>

    <p>If it is possible to submit amendments, there are two ways coming from the original document: you can either click &ldquo;Create an amendment&rdquo; in the sidebar to the right. And, if amendments are restricted to affect only one paragraph, you can alternatively choose the paragraph you want to amend and click on the &ldquo;edit&rdquo;-icon that would appear if you hover over that paragraph.</p>

    <figure class="helpFigure center">
        <img src="/img/help/AmendmentCreating1.png" alt="Screenshot: Create an amendment from the motion view">
    </figure>

    <p>Now you will be given the original text, in an editable way. You can change the text to match your proposed version. The changes you make will be marked using colors: removed words in the original text will be marked red, and inserted words green. Finally, once done, you can enter your contact data and submit the amendment. Depending on the procedures of the organization, the amendment will either be visible right away or needs to be explicitly published by an admin.</p>

    <figure class="helpFigure center">
        <img src="/img/help/AmendmentCreating2.png" alt="Screenshot: Writing an amendment">
    </figure>

    <p>Once the amendment is visible, other users can reach your suggestion in two ways: for once, it will be linked to at the home page and the bottom of the motion page. On the other side, the original text will be annotated with bookmarks at the side, indicating those places where suggestions for improvement exist. By tapping on these bookmarks (or hovering over them with the mouse), the concrete suggestion will become visible in the context of the full text.</p>

    <figure class="helpFigure center">
        <img src="/img/help/AmendmentCreating3.png" alt="Screenshot: The published amendment in the context of the full motion text">
    </figure>

    <p>This way, users like delegates can easily read the motion, draft resolution or draft party platform, while seeing which parts might be controversial or where suggestions / amendments exist.</p>

    <h2 id="setup">Setting up amendments</h2>

    <p>To let users create amendments, you will of course first need an amendable document. This could be a motion or petition by another users, or a text provided by the board of an organization, like the draft for a resolution.</p>

    <p>Strictly speaking, there is also a third option: amendments to the statutes. Here, there is also an amendable base document (a statutory document of the organization), but that document will not be displayed like a regular motion / resolution on the home page. Instead, amendments to that document will be displayed the same way as stand-alone motions. We will explain statutory amendments more in detail in a later tutorial.</p>

    <p>We <a href="/help/member-motion">already explained</a> how to allow users to submit their own motions. If the documents are provided by admins instead (that is, the permission to submit a motion is set to &ldquo;Nobody&rdquo; or &ldquo;Admins&rdquo;), you will find the link to create the base document in the admin motion list, at the top of the page a prominent &ldquo;Create&rdquo; button. There, you will have the choice which type of motion to create, independent of any permissions.</p>

    <p><strong>A warning regarding long texts</strong>: when a rather long text is to be published on Antragsgrün, with the option for members to suggest amendments, we strongly encourage to not publish the whole text in one large page, but to split it into several chapters and have each chapters be its own document. Not only is a 100 page document hard to handle for users, if it’s all on one page. Also, for the server that will host Antragsgrün, the load increases with both the length of the document, as well as with the number of amendments per document. Ten documents with 10 pages and 20 amendments each are therefore way less likely to cause troubles for the server than one document of 100 pages and 200 amendments!</p>

    <p>A hint regarding custom document templates: if you create a custom template with different kinds of section types (like PDF attachments or image uploads), please mind that only sections of the simple type &ldquo;Text&rdquo; and &ldquo;Title&rdquo; are amendable, and only if they are marked as such via the checkbox &ldquo;In Amendments&rdquo;. There is no issue with having multiple text sections being amendable, if wanted, though. The default template for motions has the &ldquo;Reason&rdquo; section set to be non-amendable, as this is how most organizations have their procedures set up regards to amendments, but this can be set up differently by simply activating the &ldquo;In Amendments&rdquo; checkbox.</p>

    <h3 id="permissions">Permissions</h3>

    <p>Setting up who is allowed to create amendments for a motion of a particular motion type is very similar to setting up who is allowed to create the motion itself. That is, at the motion type under &ldquo;Permissions&rdquo; -> &ldquo;Create amendments&rdquo;. A more detailed explanation of the different options can be found in the tutorial on how to set up motions.</p>

    <h3 id="singleparagraph">Restricting amendments to one paragraph</h3>

    <p>Next to the permissions, there is an additional option for amendments: &ldquo;Amendments may only change one paragraph&rdquo;. As the name already indicates, it controls if members can create amendments that are changing the text all over the document, or if they need to restrict themselves to one paragraph that has to be chosen by the user before modifying it. Which version to prefer depends a lot on the dynamics of the respective organization and the kinds of changes that are to be encouraged by the system:</p>
    <ul>
        <li>Only one paragraph may be amended (at a time): this option is typically chosen to encourage users to make smaller, local changes. If a user wants to change multiple paragraphs, they need to submit multiple amendments, one per amended paragraph.</li>
        <li>Multiple paragraphs may be amended: this allows more complex amendments, especially also amendments that affect the structure of the document (like moving an aspect of a motion from one paragraph to another one).</li>
    </ul>

    <p>If the amendment is restricted to one paragraph, this can be restricted even further by selecting &ldquo;…and only one specific place&rdquo;. This can be chosen if only single words or phrases are to be changed. As this is very restrictive, we recommend to not use this unless amendments are really only to change individual words, numbers etc.</p>

    <h3 id="globalalternatives">Global Alternatives / Editorial Hints</h3>

    <p>There are two special kinds of amendments for simplifying the workflows and reduce visual clutter, that need to be explicitly activated by the admin though.</p>

    <p>&ldquo;<strong>Global Alternative</strong>&rdquo; is a flag that users (or admins, afterwards) can set for an amendment to indicate that this amendment so fundamentally changes the motion that there is no point in trying to show it in &ldquo;Change Mode&rdquo;. These amendments are typically created by the user by removing the whole motion text and replacing it by a complete new text. In the regular motion view, under normal circumstances, this would appear as a change at every single paragraph (deleting that paragraph), and while merging amendments into the text, it would be mutually exclusive with each other amendment. Therefore, by marking an amendment as global alternative, it will not be appear as a bookmark right of the motion text anymore, and be listed as alternative at the bottom of the motion text instead. It will also not be suggested to be merged into the motion text.<br>
        Mind that an amendment can only be marked as a global alternative if amendments are not restricted to one paragraph.</p>

    <p><strong>Editorial Hints</strong> are amendments that are referring to rather stylistic or structural issues that would involve many different changes. They are not submitted by the user to actually make all necessary changes, but by asking the editorial team in charge of creating the final resolution based on the draft to do these changes, in natural language. This puts extra work on the editorial staff in theory, but is typically still better than having an amendment that changes dozens of words at multiple different locations, making it harder for other users to read the motion text with all its amendment suggestions, and more error-prone when merging the amendments into the final resolution (due to more potential for conflicts with other amendments).</p>

    <p>Both options can be enabled and disabled in the consultation settings (not at the motion type, for a change). More specifically, at &ldquo;Settings&rdquo; -> &ldquo;This Consultation&rdquo; -> &ldquo;Amendments&rdquo;, the two points:</p>
    <ul>
        <li>Allow global alternatives</li>
        <li>Allow editorial hints.</li>
    </ul>

    <h3 id="supporters">Who may submit amendments / Supporters</h3>

    <p>For amendments, there are the same options to specify the information the submitting person needs to provide as for motions. Also the possibilities to ask for supporters, including an explicit supporting phase, are the same for amendments. So for more information about this, we are referring to the <a href="/help/member-motion">tutorial on how to create motions</a>.</p>

    <p>However, mind that by default, motions and amendments based on them have the very same setting within a motion type. So if you specify to ask for a given amount of supporters to submit a motion, the same procedure will apply for amendments. If you need different rules for amendments, remove the checkbox at &ldquo;Use same settings for amendments&rdquo; when configuring the Proposer / Supporters in the motion type. When removing it, a new section will appear, where you can set up exactly the same, only this time for amendments in particular. This way, one could set up to require a given number of supporters to submit a motion, while allowing single members (without having to look for supporters) to submit amendments.</p>
</div>
