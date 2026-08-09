<?php

use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 */

$this->title = 'Antragsgrün: Motions in multiple languages';
/** @var \app\controllers\Base $controller */
$controller = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://motion.tools/help/multi-language';
$controller->layoutParams->alternateLanuages = ['de' => 'https://antragsgruen.de/help/multi-language'];
$controller->layoutParams->addBreadcrumb('Home', '/');
$controller->layoutParams->addBreadcrumb('Help', '/help');
$controller->layoutParams->addBreadcrumb('Multi-language support');

?>
<h1>Motions in multiple languages</h1>

<div class="content managerHelpPage">

    <p><a href="/help"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Back to the main help</a></p>

    <h2 id="intro">Introduction</h2>

    <p>Antragsgrün supports maintaining motions and amendments in several languages in parallel - for example in German and English, when a consultation has an international audience. Every reader can choose the language they want to read motion text in; if a text isn't available yet in their language, another available language version is shown instead, together with a clear notice about this.</p>

    <p>This is different from <a href="/help#translation">translating Antragsgrün's own user interface</a> (menus, buttons, system e-mails, ...), which can be set up independently of this. What's described here is about the actual <strong>content</strong> of motions - the text that submitters write themselves.</p>

    <h2 id="setup">Setup</h2>

    <p>Multi-language support is disabled by default and needs to be enabled first on the level of the site (not the individual consultation): under &ldquo;Settings&rdquo; → &ldquo;Multi-language support&rdquo; you can turn the feature on and choose the languages you want to support. One of the selected languages is automatically the consultation's &ldquo;primary&rdquo; language - it's derived from the consultation's base language setting elsewhere and doesn't need to be configured separately.</p>

    <p>If only a single language is selected (or the feature isn't enabled at all), Antragsgrün behaves exactly as before: no language picker, no additional fields in the motion type administration. Existing, already configured consultations are therefore unaffected by this new feature unless it's actively used.</p>

    <h3 id="motiontypes">Setting up motion types</h3>

    <p>Once multi-language support is enabled, every motion section (e.g. title, motion text, reason) in the motion type administration gains an additional language field. If several sections are meant to be parallel translations of each other - e.g. a German and an English motion text - they are additionally linked together via a shared &ldquo;grouping&rdquo; field (a free-text identifier, e.g. &ldquo;motiontext&rdquo;). This tells Antragsgrün that these sections are, content-wise, the same text in different languages.</p>

    <p>When a new motion type is created from one of the built-in templates (e.g. &ldquo;Motion&rdquo; or &ldquo;Statute amendment&rdquo;), Antragsgrün automatically creates one correctly grouped section per supported language while multi-language support is active - including a label appropriate to each language (e.g. &ldquo;Motion text&rdquo; vs. &ldquo;Antragstext&rdquo;). Sections that don't hold free text (images, PDFs, tabular data) are deliberately not duplicated, since their content is typically language-independent anyway.</p>

    <h2 id="submitting">Submitting motions</h2>

    <p>When submitting a motion or amendment, regular members only see - and therefore only fill in - the fields for their own, currently selected language. Sections for other languages stay empty at first, unless <a href="#autotranslation">automatic translation</a> is enabled (see below).</p>

    <p>Administrators, on the other hand, still see and can edit every language version at once when editing a motion, and can fill in missing translations by hand directly there.</p>

    <h2 id="reading">Reading view and language picker</h2>

    <p>Once a site is set up for multiple languages, a language picker appears in the navigation bar as a row of small flag icons - one for each available language other than the one currently active. The chosen language is remembered in the session for the rest of the visit; without an explicit choice, Antragsgrün first tries to suggest a matching language based on the browser's language settings.</p>

    <p>If a motion section doesn't exist yet in the selected language, Antragsgrün shows another language version that does have content instead - together with a clearly visible notice that this isn't actually the requested language. This way, no information is ever hidden just because a translation is still missing. The same applies to the motion's title: if it doesn't exist in the selected language, an existing version is shown instead.</p>

    <h2 id="autotranslation">Automatic translation (optional)</h2>

    <p><strong>Automatic translation is a fully optional add-on.</strong> Without it, multi-language support works exactly as described above: missing language versions simply stay empty (or the fallback language is shown with a notice) until someone fills them in by hand.</p>

    <p>Antragsgrün does, however, ship with a generic mechanism that lets a plugin automatically fill in an empty motion or amendment section. This mechanism is deliberately not hard-wired to &ldquo;translation&rdquo; specifically: technically, a plugin merely decides what content to fill an empty section with, and may draw on any of the motion's other sections while doing so. A translation plugin uses this to produce a translation from an already-existing language version; in principle, the very same mechanism could power a completely different plugin as well - for example one that automatically generates a short abstract of the motion text.</p>

    <p>Antragsgrün ships with an example plugin that generates translations via Anthropic's Claude API. Since the underlying mechanism is kept open, <strong>any translation service</strong> (e.g. DeepL) can in principle be hooked up through its own, correspondingly adapted plugin - Antragsgrün itself doesn't mandate a specific provider. If no such plugin is active, automatic translation is simply unused.</p>

    <p>For amendments, automatic translation takes one particularity into account: to keep the translated change as close as possible to the already-existing translation of the motion text (so that the difference shown between motion and amendment in the target language matches the one in the original language), the already-translated motion text is passed to the translation plugin as a reference. This way, only the passages that actually changed are newly translated, rather than unchanged parts ending up worded differently by chance on every translation.</p>

    <p>Automatically generated translations are marked as such internally. Once an automatically translated section is edited by hand afterwards, it is treated as regular, human-written text from that point on.</p>

    <p>If you're interested in hooking up your own translation service, please feel free to contact us.</p>
</div>
