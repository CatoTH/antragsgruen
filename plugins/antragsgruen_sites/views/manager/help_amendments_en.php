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
    <ul class="tocFlat">
        <li>
            <a href="#userview" onClick="$('#userview').scrollintoview({top_offset: -30}); return false;">Amending as a user</a>
        </li>
    </ul>

    <p><strong>Introduction</strong></p>

    <p>In this tutorial, we first introduce how amending a document looks from the point of view of a regular user. Then we will explain some of the most important ways that administrators can adapt the amendment flow to the requirements of their respective organizations.</p>

    <p>We do assume a familiarity with the tutorial on <a href="/help/member-motion">how to enable submission of motions</a>. Many settings to change details of the amendment submission are similar to those regarding motions and resolutions, so those that are very similar will be covered more briefly here.</p>

    <h2 id="userview">Amending as a user</h2>

    <p>Amendments are a constructive way to propose improvements to a given document (like a motion, the draft of a party platform or a draft resolution). To do so, one submits a concrete improved version of the text that, ideally, can be directly merged into the original document.</p>

    <p>If it is possible to submit amendments, there are two ways coming from the original document: you can either click „Create an amendment“ in the sidebar to the right. And, if amendments are restricted to affect only one paragraph, you can alternatively choose the paragraph you want to amend and click on the „edit“-icon that would appear if you hover over that paragraph.</p>

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

    <p>TO BE CONTINUED...</p>
</div>
