<?php

declare(strict_types=1);

namespace app\plugins\test_stub_autofill_even_only;

use app\models\db\{Amendment, AmendmentSection, Motion, MotionSection};
use app\plugins\ModuleBase;

/**
 * Test fixture only - fills only sections whose sectionId is even, leaving odd ones for the next
 * plugin in line. Used by SectionAutofillTest, together with plugins/test_stub_autofill (which fills
 * everything), to verify components/SectionAutofill.php correctly offers a plugin's leftover sections
 * to the next active plugin rather than giving up after the first one. Not meant to be listed in any
 * real config.json.
 */
class Module extends ModuleBase
{
    public const DUMMY_CONTENT = 'dummy-even';

    /**
     * @param MotionSection[] $sections
     * @return array<int, string>
     */
    public static function fillEmptyMotionSectionsContent(Motion $motion, array $sections): array
    {
        return self::fillEvenOnly($sections);
    }

    /**
     * @param AmendmentSection[] $sections
     * @return array<int, string>
     */
    public static function fillEmptyAmendmentSectionsContent(Amendment $amendment, array $sections): array
    {
        return self::fillEvenOnly($sections);
    }

    /**
     * @param MotionSection[]|AmendmentSection[] $sections
     * @return array<int, string>
     */
    private static function fillEvenOnly(array $sections): array
    {
        $result = [];
        foreach ($sections as $section) {
            if ($section->sectionId % 2 === 0) {
                $result[$section->sectionId] = self::DUMMY_CONTENT;
            }
        }

        return $result;
    }
}
