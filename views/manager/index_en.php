<?php
use app\components\UrlHelper;
use app\models\db\Site;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 * @var \app\controllers\Base $controller
 */

$this->title = 'Antragsgrün - Motion.Tools - Managing motions and amendments online';
$controller  = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://motion.tools/';
$controller->layoutParams->alternateLanuages = ['de' => 'https://antragsgruen.de/'];

/** @var \app\models\settings\AntragsgruenApp $params */
$params = \Yii::$app->params;

?>
<h1>Antragsgrün / Motion.Tools<br>
    <small>The Online Motion Administration for Associations Conventions, General Assemblies and Party Conventions.
    </small>
</h1>

<div class="content infoSite">
    <p>Antragsgrün offers a clear and efficient tool for the effective administration of motions, amendments and
        candidacies: from submission to administration and print template.</p>

    <p>A number of organisations are already using the tool successfully such as the federal association of the German
        Green Party or the German Federal Youth Council. It can be easily adapted to a variety of scenarios.</p>
</div>

<h2 id="funktionen" class="green">Core functions</h2>

<div class="content infoSite">
    <ul>
        <li><strong>Submit motions, proposals and discussion papers online</strong></li>
        <li><strong>Clear amendment</strong></li>
        <li><strong>Submitted amendments are displayed directly in the relevant text section</strong></li>
        <li><strong>Discuss motions</strong><br>
            Motions as well as amendments can be commented upon, either as a whole document or per paragraph. Depending
            on preference, the comments function can be open to everybody or restricted to registered users. No comments
            required? The comments function can be easily deactivated.
        </li>
        <li><strong>Sophisticated administration tools</strong><br>
            Automatic email messaging for all essential results.<br>
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
            E-Mail-Notifications<br>
            Compatible with OpenSlides 1 and 2<br>
            RSS-Feeds
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
            <span class="glyphicon glyphicon-chevron-right"></span>
            Manual / Detailed description of the functionality
        </a>
    </p>
</div>

<h2 id="selbst_nutzen" class="green">Pricing / Testing</h2>

<div class="content infoSite">
    <p>Antragsgrün is open source software and can be installed for free on servers. The source code is available on <a
                href="https://github.com/CatoTH/antragsgruen">Github</a>. On request, we can offer professional support.
    </p>

    <p style="margin-top: 25px;"><strong>Free testing</strong></p>
    <p>If you want to test the system to see if it suits your purpose, you can do so easily by creating your own test
        version without the need to provide contact details. The version is available for three days:
    </p>
    <p style="text-align: right;">
        <a href="http://sandbox.motion.tools/createsite?language=en" class="btn btn-default">Create test version</a>
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
