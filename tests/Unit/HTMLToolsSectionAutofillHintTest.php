<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\HTMLTools;
use app\models\db\AmendmentSection;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

/**
 * Covers HTMLTools::getSectionAutofillHint() - the admin-facing "this text was generated
 * automatically" notice rendered above an auto-filled section's edit field (see
 * views/admin/motion/update.php, views/admin/amendment/update.php).
 */
#[Group('database')]
class HTMLToolsSectionAutofillHintTest extends DBTestBase
{
    public function testReturnsEmptyStringForARegularSection(): void
    {
        $section = new AmendmentSection();

        $this->assertSame('', HTMLTools::getSectionAutofillHint($section));
    }

    public function testReturnsAHintForAnAutofilledSection(): void
    {
        $section = new AmendmentSection();
        $section->markAsAutofilled('translation_claude');

        $hint = HTMLTools::getSectionAutofillHint($section);

        $this->assertNotSame('', $hint);
        $this->assertStringContainsString('alertAutofillHint', $hint);
    }
}
