<?php

use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Discuss.green - The Green Online Motion Administration';
/** @var \app\controllers\Base $controller $controller */
$controller = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://discuss.green/free-hosting';

$layout                   = $controller->layoutParams;
$layout->bodyCssClasses[] = 'manager_faq';
$layout->fullWidth        = true;

?>
<h1>About free hosting</h1>

<div class="content freeHostingPage">

    <h2>Who qualifies for free hosting?</h2>

    <p>
        We provide free hosting for green parties in Europe. This includes all member parties of the European Green
        Party as well as all members of the Federation of Young European Greens, including local groups and working
        groups. In doubt, we take a rather liberal approach here – so if you want to use it for any organization that
        feels associated with the green party, just go ahead (or contact us and ask).
    </p>
    <p>There is no formal review.</p>

    <h2>Is there a guaranteed uptime / availability?</h2>

    <p>
        For the free hosting, no. We have a pretty good record of keeping the system running, but if you need additional
        security about the service being available at a specific time (like during a congress), please contact us
        for a Service Level Agreement. Of cause, you can also operate your own instance of this software on your own
        infrastructure to ensure maximum availability.
    </p>

    <h2>What about other languages than english, german and french?</h2>

    <p>
        We’re very interested in including further translations into this software. We’re taking an open-source-approach
        for that: if you’re interested in a localized version in your language and are willing to put some effort into
        this, you can simply translate the texts and send us the translations. If mostly complete, we will incorporate
        this into the next release.
    </p>

    <p>
        To start translating, you can simply create a new site on discuss.green, choosing english as base language. In
        the newly created site, you can start translating in the administration at "Edit the language”.
    </p>

    <p>If you have any question about translating it, just contact us.</p>

    <h2>Can this system be used with custom domains?</h2>

    <p>
        Yes. You can either download the open source software and install it on your own server using the desired
        domain. Or you can contact us so we can figure out if we can provide hosting for your organization using another
        domain.
    </p>

    <h2>Can we change the design to match our own design guidelines?</h2>

    <p>
        Yes, but but with a bit of effort. You can either download the software and create your own design plugin. A
        rough guide on how to do so can be found in the
        <a href="https://github.com/CatoTH/antragsgruen#custom-themes-as-plugin">README</a>.
        Or you can mandate us to develop a new theme for your organization.
    </p>
</div>
