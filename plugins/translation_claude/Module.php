<?php

declare(strict_types=1);

namespace app\plugins\translation_claude;

use app\models\db\{Amendment, AmendmentSection, Motion, MotionSection};
use app\plugins\ModuleBase;

/**
 * Integrates Claude (Anthropic's API) as a translation backend for the section-autofill mechanism
 * (see components/SectionAutofill.php): when a section is empty because the site has several
 * supported languages and the submitter only filled in their own (D10 in
 * multilanguage-implementation.md), this plugin translates it from whichever other language of the
 * same motion/amendment does have content - see SectionTranslator for how the source is chosen and
 * Prompts for what is actually sent to Claude.
 *
 * Inactive (returns null, so SectionAutofill leaves the section empty) whenever
 * plugins/translation_claude/credentials.json is missing or incomplete - see
 * credentials.example.json.
 */
class Module extends ModuleBase
{
    public static function fillEmptyMotionSectionContent(Motion $motion, MotionSection $section): ?string
    {
        $credentials = static::loadCredentials();
        if ($credentials === null) {
            return null;
        }

        return (new SectionTranslator($credentials))->translateMotionSection($motion, $section);
    }

    public static function fillEmptyAmendmentSectionContent(Amendment $amendment, AmendmentSection $section): ?string
    {
        $credentials = static::loadCredentials();
        if ($credentials === null) {
            return null;
        }

        return (new SectionTranslator($credentials))->translateAmendmentSection($amendment, $section);
    }

    /**
     * Indirection (rather than calling Credentials::load() directly above) purely so tests can
     * override this in a subclass to force the "no credentials" branch deterministically - depending
     * on whether plugins/translation_claude/credentials.json happens to exist locally would make
     * those tests pass or fail depending on the developer's own configuration.
     */
    protected static function loadCredentials(): ?Credentials
    {
        return Credentials::load();
    }
}
