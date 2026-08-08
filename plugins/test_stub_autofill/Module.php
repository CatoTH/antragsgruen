<?php

declare(strict_types=1);

namespace app\plugins\test_stub_autofill;

use app\models\db\{Amendment, AmendmentSection, Motion, MotionSection};
use app\plugins\ModuleBase;

/**
 * Test fixture only - always fills an empty section with the literal string "dummy". Used by
 * SectionAutofillTest to exercise components/SectionAutofill.php's generic dispatch logic
 * (empty-check, dispatch, metadata marking) independently of any real translation backend such as
 * plugins/translation_claude, which has its own, separately-tested logic. Not meant to be listed in
 * any real config.json.
 */
class Module extends ModuleBase
{
    public const DUMMY_CONTENT = 'dummy';

    public static function fillEmptyMotionSectionContent(Motion $motion, MotionSection $section): ?string
    {
        return self::DUMMY_CONTENT;
    }

    public static function fillEmptyAmendmentSectionContent(Amendment $amendment, AmendmentSection $section): ?string
    {
        return self::DUMMY_CONTENT;
    }
}
