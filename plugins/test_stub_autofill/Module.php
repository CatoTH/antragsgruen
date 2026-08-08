<?php

declare(strict_types=1);

namespace app\plugins\test_stub_autofill;

use app\models\db\{Amendment, AmendmentSection, Motion, MotionSection};
use app\plugins\ModuleBase;

/**
 * Test fixture only - always fills every section it's offered with the literal string "dummy". Used
 * by SectionAutofillTest to exercise components/SectionAutofill.php's generic dispatch logic
 * (empty-check, batch dispatch, metadata marking) independently of any real translation backend such
 * as plugins/translation_claude, which has its own, separately-tested logic. Not meant to be listed
 * in any real config.json.
 */
class Module extends ModuleBase
{
    public const DUMMY_CONTENT = 'dummy';

    /**
     * @param MotionSection[] $sections
     * @return array<int, string>
     */
    public static function fillEmptyMotionSectionsContent(Motion $motion, array $sections): array
    {
        return self::fillAll($sections);
    }

    /**
     * @param AmendmentSection[] $sections
     * @return array<int, string>
     */
    public static function fillEmptyAmendmentSectionsContent(Amendment $amendment, array $sections): array
    {
        return self::fillAll($sections);
    }

    /**
     * @param MotionSection[]|AmendmentSection[] $sections
     * @return array<int, string>
     */
    private static function fillAll(array $sections): array
    {
        $result = [];
        foreach ($sections as $section) {
            $result[$section->sectionId] = self::DUMMY_CONTENT;
        }

        return $result;
    }
}
