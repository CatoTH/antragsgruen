<?php

use app\components\UrlHelper;
use app\models\db\Site;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$assets = \app\plugins\green_manager\Assets::register($this);

$this->title = 'Discuss.green - Managing resolutions, motions and amendments online';
/** @var \app\controllers\Base $controller */
$controller  = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl      = 'https://discuss.green/';
$controller->layoutParams->alternateLanuages = ['de' => 'https://antragsgruen.de/'];

$layout                   = $controller->layoutParams;
$layout->bodyCssClasses[] = 'manager_index';
$layout->fullWidth        = true;

$params = \app\models\settings\AntragsgruenApp::getInstance();

?>
<h1>Discuss.green<br>
    <small>Manage Resolutions and Amendments on Conventions and Congresses.</small>
</h1>

<div class="content infoSite">
    <p>
        Discuss.green offers a clear and efficient tool for the administration of motions, resolutions, amendments and candidacies,
        specifically designed for conventions and congresses of parties, social initiatives and organizations.
    </p>

    <p>
        It has been created for managing both national and regional congresses of the German Greens
        and is by now used by a variety of organizations within and beyond the green party.
    </p>

    <p>
        It's available as open source and we provide free hosting for all green parties.
    </p>
</div>


<h2 id="who_uses_it" class="green">Who is already using it?</h2>
<div class="content contentWhoUsesIt">
    <div class="list">
        <a href="https://motiontool.europeangreens.eu/" target="_blank">
            <img src="<?= $assets->baseUrl ?>/logo-egp.svg" alt="European Greens">
            <div class="name">European Greens</div>
            <div class="hint">EGP Online Congress</div>
        </a>
        <a href="https://antraege.gruene.de/" target="_blank">
            <img src="<?= $assets->baseUrl ?>/logo-b90.svg" alt="Bündnis 90 / Die GRÜNEN">
            <div class="name">Bündnis 90 / Die GRÜNEN</div>
            <div class="hint">Federal Convention</div>
        </a>
    </div>
    <p>
        ... as well as dozens of other green organizations (like the Greens in
        <a href="https://gruene-wien.antragsgruen.de/" target="_blank">Vienna</a> and
        <a href="https://edinburghgreens.discuss.green/" target="_blank">Edinburgh</a>,
        non-green parties like the Austrian NEOS or the Dutch Volt,
        and civil organizations like the <a href="https://yfj-votes.motion.tools/" target="_blank">European Youth Forum</a> or the
        <a href="https://tooldoku.dbjr.de/category/antragsgruen/" target="_blank">German Federal Youth Council</a>.
    </p>
</div>

<h2 id="funktionen" class="green">Core functionality</h2>
<div class="content infoSite">
    <ul>
        <li><strong>Submit motions, resolutions, discussion papers and applications online</strong><br>
            Flexible and user-friendly submission of motions and applications<br>
            Intuitive creation of amendments<br>
            Creating final resolutions based on the motions and amendments to them.<br>
            Managing the agenda of a congress
        </li>
        <li><strong>Discuss motions</strong><br>
            Motions as well as amendments can be commented upon, either as a whole document or per paragraph. Depending
            on preference, the comments function can be open to everybody or restricted to registered users.<br>
            <small>(No comments required? The comments function can be easily deactivated.)</small>
        </li>
        <li><strong>Sophisticated administration tools</strong><br>
            Filter and sorting options for all motions / amendments<br>
            If required: assessment of each submitted motion / amendment with regards to permissibility by the programme
            commission prior to publication.<br>
            The page visibility can be specified: from “openly visible” to “only for invited members”.<br>
            All user interface texts can be adapted.<br>
            Flexible layout: the site provides different layout variants and the logos are exchangeable. If required, we
            can adapt the design to your CI specifications.
        </li>
        <li><strong>Diverse export options and notifications</strong><br>
            Automatically generated PDFs for all motions and amendments with different templates<br>
            Export to different office formats (OpenDocument text, spreadsheet)<br>
            E-Mail-Notifications
        </li>
        <li><strong>Technically mature, data privacy-friendly</strong><br>
            Open source software, verifiable functionality. No black box.<br>
            Our servers are all located within the European Union.<br>
            No external tracking services, advertisements or similar.<br>
            Standard encrypted transmission.<br>
            Extensively tested software with hundreds of automated tests as well as operational use during large events
            with several hundred submitted motions.
        </li>
    </ul>

    <p style="text-align: center; font-weight: bold;">
        <a href="<?= Html::encode(UrlHelper::createUrl('manager/help')) ?>">
            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
            Manual / Detailed description of the functionality
        </a>
    </p>
</div>

<h2 id="create_version" class="green">Use it</h2>
<div class="content">
    <p>Discuss.green / Antragsgrün is open source software and can be installed on any web server that supports PHP and MySQL.</p>
    <p>
        For european green parties, we provide <strong>free hosting</strong> of sites on this domain.
        You can start using or evaluating this tool by answering a couple of questions about your use case and providing
        a valid e-mail-address. Creating your own instance only takes two minutes.
    </p>

    <div class="downloadCreate">
        <div>
            <a href="https://github.com/CatoTH/antragsgruen" class="btn btn-default">
                Download / Source Code
            </a>
        </div>
        <div>
            <a href="/createsite" class="btn btn-success">Test it / Create your instance</a>
        </div>
    </div>

    <p>
        <strong>About internationalization:</strong> We provide an english and german version,
        as well as a nearly-completele french version. If you are interested in helping translate this tool
        in other languages, please contact us.
    </p>
    <p>
        <a href="<?= Html::encode(UrlHelper::createUrl('manager/free-hosting')) ?>">
            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
            Details / F.A.Q. regarding the free hosting
        </a>
    </p>
</div>

<h2 id="contact" class="green">Contact</h2>

<div class="content infoSite">
    <p style="margin-top: 35px;"><strong>Are there functions missing?
            Do you require professional support and special adaptations?</strong></p>
    <p>If you need customised programming or you would like us to host Antragsgrün / Discuss.green on a designated
        domain, we implement this at an hourly rate.</p>
    <p>
        We are here to answer your questions and requests:
    </p>
    <ul>
        <li>E-Mail: <a href="mailto:info@discuss.green">info@discuss.green</a></li>
        <li>Phone: +49-1515-6024223</li>
    </ul>
</div>
