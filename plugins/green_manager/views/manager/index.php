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
$controller->layoutParams->addInlineCss('
    .homeFigure { text-align: center; }
    .homeFigure figcaption { margin-top: -20px; margin-bottom: 20px; font-size: 0.8em; font-style: italic; }
    .homeFigureAmendment img { max-width: 100%; }
    @media (min-width: 800px) {
        .homeFigureAmendment img { max-width: 600px; }
    }
    .homeFigurePrint { max-width: 230px; box-shadow: 0 0 7px rgba(0,0,0,.4); border-radius: 2px; overflow: hidden; }
    @media (min-width: 800px) {
        .homeFigurePrint { float: right; margin-left: 50px; }
    }
    @media (max-width: 799px) {
        .homeFigurePrint { margin: 20px auto; }
    }
    .homeFigurePrint img { max-width: 100%; }
    .homeFigurePrint figcaption { margin-bottom: 5px; }

    @media (min-width: 800px) {
        .homeFigureTwoHolder { display: flex; flex-direction: row; margin-bottom: 30px; margin-top: -25px; }
        .homeFigureTwoHolder > * { flex-basis: 50%; }
    }
    .homeFigureTwoHolder img { max-width: 100%; }
    .homeFigureSpeech figcaption { margin-top: -10px; }
');

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
        Antragsgrün is an easy-to-use online tool for NGOs, political parties, and social initiatives to collaboratively discuss resolutions, party platforms, and amendments.<br>
        It helps to manage candidacies and supports meetings by providing online votings, speaking lists, and many more features.
    </p>

    <p>
        It was originally created for managing regional congresses of the German Greens, but was quickly adopted
        on the federal and European level too, and is by now used by a variety of organizations within and beyond the green party.
    </p>

    <p>
        It's available as open source and we provide free hosting for Green parties.
    </p>
</div>

<section aria-labelledby="who_uses_it">
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
            <a href="https://ga.cdnee.org" target="_blank">Cooperation &amp; Developement Network Eastern Europe</a>.
        </p>
    </div>
</section>

<section aria-labelledby="motions">
    <h2 id="motions" class="green">Motions, amendments, resolutions, candidacies</h2>

    <div class="content infoSite">
        <p>Antragsgrün allows you to implement your whole motion process, including amendments and candidacies.</p>

        <p><strong>Motions, Resolutions, Statutes, or election programmes</strong> can be submitted and published,
            either by members or dedicated user groups like the board or eligible delegates.
            Also submitting <strong>candidacies</strong>, including images and PDF attachments is supported.</p>

        <figure class="homeFigure homeFigureAmendment">
            <img src="/img/Screenshot-Amendment-en.png" alt="Screenshot of an amendment">
            <figcaption>
                Amendments can be shown either separately or (as shown here) in the context of the motion.
            </figcaption>
        </figure>

        <p>Besides allowing other members to <strong>comment</strong> on published documents, Antragsgrün encourages
            constructive proposals by providing an easy-to-use way to <strong>submit amendments</strong> to documents,
            to be discussed and decided by the board or the member's General Assembly.</p>

        <figure class="homeFigure homeFigurePrint">
            <img src="/img/Screenshot-Print.png" alt="Druckvorlage">
            <figcaption>
                Diverse export options, like a print template
            </figcaption>
        </figure>

        <p><strong>Antragsgrün supports organizing assemblies</strong> by many different features:</p>

        <ul>
            <li>Printing templates can be created automatically<br>(Export as PDF, Spreadsheet or text documents)</li>
            <li>Accepted amendments can be merged into the original document - creating a final resolution works even with many amendments and ad-hoc changes</li>
            <li>E-Mail-Notification on relevant events both for administrators and participants</li>
            <li>Defining responsibilities for motions and topics, internal admin tools for decision-finding</li>
            <li>... and much more.</li>
        </ul>

        <p>
            <a href="https://motion.tools/help" style="font-weight: bold;">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                Get a more exhaustive list of features
            </a><br><br>
            <a href="https://motion.tools/help/member-motion">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                Tutorial: Submission of motions and resolutions
            </a><br>
            <a href="https://motion.tools/help/amendments">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                Tutorial: Submission of amendments
            </a><br>
        </p>

        <br style="clear: both;">
    </div>
</section>

<section aria-labelledby="onsite">
    <h2 id="onsite" class="green">On Site support for conventions</h2>

    <div class="content infoSite">
        <p>Antragsgrün helps with working with motions and amendments on conventions themselves:</p>

        <ul>
            <li>An easy administration of the <strong>Agenda</strong></li>
            <li><strong>Speaking lists</strong></li>
            <li><strong>Roll Calls and votings</strong> are supported</li>
            <li>Full-Screen-mode for <strong>projectors</strong> for all relevant content</li>
        </ul>
    </div>


    <div class="homeFigureTwoHolder">
        <figure class="homeFigure homeFigureSpeech">
            <img src="/img/Screenshot-Speaking.png" alt="Screenshot of a speaking list">
            <figcaption>
                Flexible administration of a speaking list
            </figcaption>
        </figure>

        <figure class="homeFigure homeFigureVoting">
            <img src="/img/Screenshot-Voting.png" alt="Screenshot of a voting">
            <figcaption>
                Votings over motions, amendments and arbitrary decisions
            </figcaption>
        </figure>
    </div>
</section>

<section aria-labelledby="flexible">
    <h2 id="flexible" class="green">Flexible and Customizable</h2>

    <div class="content infoSite">
        <p>Antragsgrün is used by a wide range of organizations is different scenarios and can be customized in many ways
            to many common settings. For example:</p>

        <ul>
            <li>The <strong>layout</strong> of the site can be changed on the site itself: the colors, fonts, the logo, and all texts and labels can be customized without effort.</li>
            <li>The <strong>submission policy</strong> of motions and amendments allows many different variants: setting deadlines, restricting it to eligible user groups, requiring screening by an administrator group, ...</li>
            <li>Integrating the system into an existing <strong>Single-Sign-On-Mechanism</strong> (like SAML) is possible and can be custom-tailored on request.</li>
        </ul>

        <p>For further features and organization-specific adaptions we <strong>professional support.</strong></p>

        <p><strong>Contact for support</strong>:<br>
            Tobias Hößl<br>
            <a href="mailto:info@antragsgruen.de">info@antragsgruen.de</a>
    </div>
</section>

<section aria-labelledby="mature">
    <h2 id="mature" class="green">Mature, Open, Privacy friendly</h2>

    <div class="content infoSite">
        <p>Antragsgrün is actively used on political conventions and assemblies <strong>since more than ten years</strong>
            and helps both small informal working group as well as large general assemblies with hundreds of delegates
            and thousands of amendments.</p>

        <p>Antragsgrün is continuously developed as <strong>Open Source</strong> (AGPL) in cooperation with
            organizations using it. Antragsgrün can, on the one side, be downloaded, installed and used for free as it is.
            The download and an installation guide can be found on <a href="https://github.com/CatoTH/antragsgruen">Github</a>.

        <p>On the other side, we also provide <strong>professional support</strong>,
            hosting and the implementation of new features and organization-specific changes on a per-request basis.</p>

        <p>We care a lot about data privacy: we do not collect unnecessary personal data, we <strong>do not use trackers</strong>
            and don't do Ads. Our main servers are located in the European Union, hosting in the U.S. is available on request.</p>
    </div>
</section>

<h2 id="create_version" class="green">Use it</h2>
<div class="content">
    <p>Discuss.green / Antragsgrün is open source software and can be installed on any web server that supports PHP and MySQL.</p>
    <p>
        For European and Canadian Green parties, we provide <strong>free hosting</strong> of sites on this domain.
        You can start using or evaluating this tool by answering a couple of questions about your use case and providing
        a valid e-mail-address. Creating your own instance only takes about two minutes.
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
        <strong>About internationalization:</strong> We provide an English and German version,
        as well as nearly-completele versions in French and other community-contributed languages.
        If you are interested in helping translate this tool in other languages, please contact us.
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
    </ul>
</div>
