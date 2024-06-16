<?php

use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün: Progress Reports';
/** @var \app\controllers\Base $controller */
$controller = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://motion.tools/help/progress-reports';
$controller->layoutParams->alternateLanuages = ['de' => 'https://antragsgruen.de/help/progress-reports'];
$controller->layoutParams->addBreadcrumb('Home', '/');
$controller->layoutParams->addBreadcrumb('Help', '/help');
$controller->layoutParams->addBreadcrumb('Progress Reports');

?>
<h1>Progress Reports</h1>

<div class="content managerHelpPage">

    <p><a href="/help"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Back to the main help</a></p>

    <h2>Why Progress Reports?</h2>

    <p>If you use Antragsgrün to archive resolutions, it is possible to also add progress reports to each resolution. So eligible members can not only read the resolutions themselves, but also the progress that the acting organization is making in implementing them.</p>

    <p>Progress reports therefore add an additional text section to the resolution, the one that holds the protocol of the progress made since its publication. This section behaves differently from regular text sections in the following aspects:</p>
    <ul>
        <li>There is a separate permission to allow users to create and edit progress reports, independently from the main resolution text. Typically, only special admins will be allowed to modify the text of a resolution itself under very special circumstances, while the permission to change the progress report will be given out to a wider user group, like to the secretariat of the organization or dedicated working groups.</li>
        <li>Editing the progress report is way quicker and informal than editing the resolution text. It is possible to edit it directly from within the main motion view, without having to go to the admin view first.</li>
        <li>The progress report has a &ldquo;last changed on&rdquo; and &ldquo;author&rdquo; field that is independent of the resolution itself.</li>
    </ul>

    <p>In the remainder of this page, we will describe how to set up and how to use progress reports.</p>

    <h2>Use</h2>

    <p>While creating the resolution (as part of the &ldquo;Merge amendments&rdquo; function), you will be presented a choice if to create a new motion, a preliminary resolution or a final resolution. If you choose a resolution, and a compatible motion type exists (see &ldquo;Setup&rdquo;), a dropdown with compatible motion types will appear, the progress reports type being one of them. Select it and proceed as normal.</p>

    <figure class="helpFigure center">
        <img src="/img/help/ProgressReport1.png" alt="Screenshot: Creating a resolution with progress report">
    </figure>

    <p>By default, the generated resolution will be presented as any resolution, without a progress report.</p>

    <p>Eligible editorial persons, however, will be given the chance to create and edit the progress report, just below the resolution text. Once a text is entered, it will be visible to all users.</p>

    <figure class="helpFigure center">
        <img src="/img/help/ProgressReport2.png" alt="Screenshot: Editing the progress report">
    </figure>

    <h2>Setup</h2>

    <p>Setting up progress reports has two aspects: creating a compatible motion type, and granting permissions to the users that are allowed to write them.</p>

    <h3>Creating a Motion Type</h3>

    <p>If you use the default motion type for motions (that is, a title, a motion text, and optionally a reason), it will be fairly simple to create a motion type for the progress reports: In the settings, choose &ldquo;Create new motion type&rdquo;, and select &ldquo;Progress reports&rdquo; as template there. A new motion type will be created with the following noteworthy settings:</p>
    <ul>
        <li>Only Admins are allowed to create new documents (resolutions)</li>
        <li>Besides the default sections &ldquo;Title&rdquo; and &ldquo;Resolution&rdquo;, there will not be a &ldquo;Reason&rdquo; (as the reason is typically removed when creating a resolution) section, but a &ldquo;Progress&rdquo; section of type &ldquo;Editorial Text&rdquo; instead.</li>
    </ul>

    <p>If the motion type you use for motions has additional sections besides the ones mentioned above, you will need to make sure that the new motion type for the progress reports is compatible with it. Basically, for each non-optional text section in the original type, there needs to be a corresponding text section in the progress report motion type. If there is none, you will not be able to select the motion type when creating the resolution.</p>

    <h3>Grant Permissions</h3>

    <p>The relevant permission to edit progress reports is called &ldquo;Manage editorial sections / Progress reports&rdquo;. Users can receive this permission in three ways:</p>
    <ul>
        <li>Consultation and Site admins have this permission automatically</li>
        <li>You can assign users to the user group &ldquo;Progress reports&rdquo;. This group grants said permission to all members.</li>
        <li>Alternatively, you can create a new user group, add members, and grant this permission to this group. This would be the approach to take if you want to define users that can only edit the progress reports of a subset of all resolutions - for example, having different user groups, with each being responsible for a specific Topic / Tag. (Use the &ldquo;Restricted to: Tag&rdquo; choice when defining the permissions in this case).</li>
    </ul>
</div>
