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
 * Prompts for what is actually sent to Claude. All of a motion's/amendment's empty sections are
 * translated in a single batched API call, not one call per section - see SectionTranslator.
 *
 * Inactive (returns an empty array, so SectionAutofill leaves every section empty) whenever
 * plugins/translation_claude/credentials.json is missing or incomplete - see
 * credentials.example.json.
 */
class Module extends ModuleBase
{
    /**
     * @param MotionSection[] $sections
     * @return array<int, string>
     */
    public static function fillEmptyMotionSectionsContent(Motion $motion, array $sections): array
    {
        $credentials = static::loadCredentials();
        if ($credentials === null) {
            return [];
        }

        return (new SectionTranslator($credentials))->translateMotionSections($motion, $sections);
    }

    /**
     * @param AmendmentSection[] $sections
     * @return array<int, string>
     */
    public static function fillEmptyAmendmentSectionsContent(Amendment $amendment, array $sections): array
    {
        $credentials = static::loadCredentials();
        if ($credentials === null) {
            return [];
        }

        return (new SectionTranslator($credentials))->translateAmendmentSections($amendment, $sections);
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
