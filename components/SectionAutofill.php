<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Amendment, AmendmentSection, Motion, MotionSection};
use app\models\settings\AntragsgruenApp;

/**
 * Fills currently-empty motion/amendment sections using whatever plugin(s) implement
 * ModuleBase::fillEmptyMotionSectionContent()/fillEmptyAmendmentSectionContent() - e.g. a plugin
 * translating from another language section of the same motion, or (not shipped with core) one
 * deriving an abstract from the main text. A no-op wherever no such plugin is active, or a section
 * already has content.
 */
class SectionAutofill
{
    public static function fillEmptyMotionSections(Motion $motion): void
    {
        foreach ($motion->getActiveSections() as $section) {
            self::fillMotionSection($motion, $section);
        }
    }

    public static function fillEmptyAmendmentSections(Amendment $amendment): void
    {
        foreach ($amendment->getActiveSections() as $section) {
            self::fillAmendmentSection($amendment, $section);
        }
    }

    private static function fillMotionSection(Motion $motion, MotionSection $section): void
    {
        if ($section->hasContentForFiltering()) {
            return;
        }

        foreach (AntragsgruenApp::getActivePlugins() as $pluginId => $pluginClass) {
            $content = $pluginClass::fillEmptyMotionSectionContent($motion, $section);
            if ($content !== null) {
                $section->setData($content);
                $section->markAsAutofilled($pluginId);
                $section->save();
                return;
            }
        }
    }

    private static function fillAmendmentSection(Amendment $amendment, AmendmentSection $section): void
    {
        // An amendment section is always pre-filled with the motion's original text, even where the
        // amendment doesn't touch it - hasContentForFiltering() (does it actually differ from the
        // original?), not raw string emptiness, is what "nothing here yet" means for an amendment.
        if ($section->hasContentForFiltering()) {
            return;
        }

        foreach (AntragsgruenApp::getActivePlugins() as $pluginId => $pluginClass) {
            $content = $pluginClass::fillEmptyAmendmentSectionContent($amendment, $section);
            if ($content !== null) {
                $section->setData($content);
                $section->markAsAutofilled($pluginId);
                $section->save();
                return;
            }
        }
    }
}
