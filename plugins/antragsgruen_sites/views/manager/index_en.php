<?php

use app\components\UrlHelper;
use app\models\db\Site;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün - Motion.Tools - Managing resolutions, motions and amendments online';
/** @var \app\controllers\Base $controller */
$controller  = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl      = 'https://motion.tools/';
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

?>
<h1>Antragsgrün / Motion.Tools<br>
    <small>Manage Resolutions and Amendments on Conventions and Congresses.</small>
</h1>

<div class="content infoSite">
    <p>Antragsgrün is an easy-to-use online tool for NGOs, political parties,
        and social initiatives to collaboratively discuss resolutions, party platforms, and amendments.
        It helps to manage candidacies and supports meetings by providing online votings,
        speaking lists, and many more features.</p>

    <p>Many organizations are already using the tool successfully such as the
        <a href="https://www.youthforum.org/">European Youth Forum</a>, the <a href="https://www.gruene.de/">German</a> and
        <a href="https://europeangreens.eu/">European Green Party</a>, and the
        <a href="https://www.frauenrat.de/shortinfo/">National Council of German Women’s Organizations</a>.
        It can be easily adapted to a variety of scenarios.</p>
</div>


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
            <a href="<?= Html::encode(UrlHelper::createUrl('manager/help')) ?>" style="font-weight: bold;">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                Get a more exhaustive list of features
            </a><br><br>
            <a href="/help/member-motion">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                Tutorial: Submission of motions and resolutions
            </a><br>
            <a href="/help/amendments">
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
            <a href="mailto:info@antragsgruen.de">info@antragsgruen.de</a><br>
            <a href="tel:+4915156024223">+49 151 56024223</a>
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

<section aria-labelledby="selbst_nutzen">
    <h2 id="selbst_nutzen" class="green">Pricing / Testing</h2>

    <div class="content infoSite">
        <p>Antragsgrün is open source software and can be installed for free on servers. The source code is available on <a
                href="https://github.com/CatoTH/antragsgruen">Github</a>. On request, we can offer professional support.
        </p>

        <p style="margin-top: 25px;"><strong>Free testing</strong></p>
        <p>If you want to test the system to see if it suits your purpose, you can do so easily by creating your own test
            version without the need to provide contact details. The version is available for at least three days:
        </p>
        <p style="text-align: right;">
            <a href="https://sandbox.motion.tools/createsite?language=en" class="btn btn-default">Create test version</a>
        </p>

        <p style="margin-top: 35px;"><strong>Are there functions missing?
                Do you require professional support and special adaptations?</strong></p>
        <p>If you need customised programming or you would like us to host Antragsgrün on a designated domain, we implement
            this at an hourly rate.</p>
        <p>
            We are here to answer your questions and requests:
        </p>
        <ul>
            <li>E-Mail: <a href="mailto:info@antragsgruen.de">info@antragsgruen.de</a></li>
            <li>Phone: +49-1515-6024223</li>
        </ul>
    </div>
</section>
