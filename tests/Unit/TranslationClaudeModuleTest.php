<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\models\db\{Amendment, AmendmentSection, Motion, MotionSection};
use app\plugins\translation_claude\Module;
use Tests\Support\Helper\TestBase;

/**
 * Covers plugins/translation_claude/Module.php's own contribution on top of SectionTranslator (which
 * has its own dedicated tests): declining (returning null) whenever no credentials.json is
 * configured, which is always the case in this test environment - see
 * plugins/translation_claude/credentials.example.json and TranslationClaudeCredentialsTest. DB-free:
 * Credentials::load() returns null before either argument is ever touched.
 */
class TranslationClaudeModuleTest extends TestBase
{
    public function testDeclinesAMotionSectionWithoutCredentials(): void
    {
        $this->assertNull(Module::fillEmptyMotionSectionContent(new Motion(), new MotionSection()));
    }

    public function testDeclinesAnAmendmentSectionWithoutCredentials(): void
    {
        $this->assertNull(Module::fillEmptyAmendmentSectionContent(new Amendment(), new AmendmentSection()));
    }
}
