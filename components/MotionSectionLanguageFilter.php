<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\IMotionSection;

/**
 * Decides, for a given reader language, which of a motion/amendment's parallel per-language
 * sections (sharing a languageGrouping) are shown. Pure and stateless, so it can be unit-tested
 * without a database. Does not change ordering - the caller (IMotion::getSortedSections()) always
 * re-sorts the result by the motion type's section order.
 */
class MotionSectionLanguageFilter
{
    /**
     * @param IMotionSection[] $sections
     * @return IMotionSection[]
     */
    public static function filter(array $sections, string $readerLanguage): array
    {
        $kept = [];

        /** @var array<string, IMotionSection[]> $groups */
        $groups = [];
        foreach ($sections as $section) {
            $language = $section->getSettings()?->getLanguage();
            if ($language === null) {
                // No language set (or no settings at all, which shouldn't normally happen at this
                // point) - valid for every language, always kept.
                $kept[] = $section;
                continue;
            }

            $groupKey = $section->getSettings()->getLanguageGrouping() ?? ('#section' . $section->sectionId);
            $groups[$groupKey][] = $section;
        }

        foreach ($groups as $group) {
            array_push($kept, ...self::resolveGroup($group, $readerLanguage));
        }

        return $kept;
    }

    /**
     * @param IMotionSection[] $group all sharing the same languageGrouping (or the same lone section)
     * @return IMotionSection[]
     */
    private static function resolveGroup(array $group, string $readerLanguage): array
    {
        $withContent = array_values(array_filter(
            $group,
            fn (IMotionSection $section): bool => $section->hasContentForFiltering()
        ));

        foreach ($withContent as $section) {
            if ($section->getSettings()->getLanguage() === $readerLanguage) {
                // The reader's own language has content - show only that, no fallback needed.
                return [$section];
            }
        }

        if (count($withContent) > 0) {
            // The reader's language has no content, but other languages do: show those, with a
            // disclaimer added by the view (IMotionSection::needsLanguageLabel()).
            return $withContent;
        }

        foreach ($group as $section) {
            if ($section->getSettings()->getLanguage() === $readerLanguage) {
                // Nothing in the group has content anywhere - keep the reader's own (empty)
                // section, matching how a single-language site already shows empty sections.
                return [$section];
            }
        }

        // Nothing has content, and the reader's language isn't even part of this group - nothing
        // meaningful to show.
        return [];
    }
}
