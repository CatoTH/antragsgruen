<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Amendment, AmendmentSection, IMotionSection, Motion, MotionSection};
use app\models\settings\AntragsgruenApp;

/**
 * Fills currently-empty motion/amendment sections using whatever plugin(s) implement
 * ModuleBase::fillEmptyMotionSectionsContent()/fillEmptyAmendmentSectionsContent() - e.g. a plugin
 * translating from another language section of the same motion, or (not shipped with core) one
 * deriving an abstract from the main text. A no-op wherever no such plugin is active, or a section
 * already has content.
 *
 * All of a motion's/amendment's empty sections are offered to a plugin in a single call, not one call
 * per section, so a plugin talking to an external API (see plugins/translation_claude) can combine
 * them into a single request instead of one per section.
 */
class SectionAutofill
{
    public static function fillEmptyMotionSections(Motion $motion): void
    {
        $emptySections = self::findEmptySections($motion->getActiveSections());
        if (count($emptySections) === 0) {
            return;
        }

        foreach (AntragsgruenApp::getActivePlugins() as $pluginId => $pluginClass) {
            $results = $pluginClass::fillEmptyMotionSectionsContent($motion, $emptySections);
            $emptySections = self::applyResults($emptySections, $results, $pluginId);
            if (count($emptySections) === 0) {
                return;
            }
        }
    }

    public static function fillEmptyAmendmentSections(Amendment $amendment): void
    {
        $emptySections = self::findEmptySections($amendment->getActiveSections());
        if (count($emptySections) === 0) {
            return;
        }

        foreach (AntragsgruenApp::getActivePlugins() as $pluginId => $pluginClass) {
            $results = $pluginClass::fillEmptyAmendmentSectionsContent($amendment, $emptySections);
            $emptySections = self::applyResults($emptySections, $results, $pluginId);
            if (count($emptySections) === 0) {
                return;
            }
        }
    }

    /**
     * @template T of IMotionSection
     * @param T[] $sections
     * @return T[]
     */
    private static function findEmptySections(array $sections): array
    {
        // An amendment section is always pre-filled with the motion's original text, even where the
        // amendment doesn't touch it - hasContentForFiltering() (does it actually differ from the
        // original?), not raw string emptiness, is what "nothing here yet" means for an amendment.
        // For a motion section, this is simply "not empty".
        return array_values(array_filter(
            $sections,
            fn (IMotionSection $section): bool => !$section->hasContentForFiltering()
        ));
    }

    /**
     * Applies a plugin's batch result to the sections it was offered, marking each filled section as
     * autofilled by $pluginId, and returns the sections still left empty for the next plugin in line.
     *
     * @template T of IMotionSection
     * @param T[] $sections
     * @param array<int, string> $results sectionId => content
     * @return T[]
     */
    private static function applyResults(array $sections, array $results, string $pluginId): array
    {
        $stillEmpty = [];
        foreach ($sections as $section) {
            if (!isset($results[$section->sectionId])) {
                $stillEmpty[] = $section;
                continue;
            }
            $section->setData($results[$section->sectionId]);
            $section->markAsAutofilled($pluginId);
            $section->save();
        }

        return $stillEmpty;
    }
}
