<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\models\db\{Amendment, AmendmentSection, Motion, MotionSection};
use Tests\Support\Helper\{TestBase, TranslationClaudeModuleWithoutCredentials};

/**
 * Covers plugins/translation_claude/Module.php's own contribution on top of SectionTranslator (which
 * has its own dedicated tests): declining (returning null) whenever no credentials are configured.
 * Uses TranslationClaudeModuleWithoutCredentials (a Module subclass overriding the protected
 * loadCredentials() seam) rather than calling Module directly, so this doesn't depend on whether a
 * real plugins/translation_claude/credentials.json happens to exist locally - see
 * TranslationClaudeCredentialsTest for Credentials::load() itself. DB-free: loadCredentials() returns
 * null before either argument is ever touched.
 */
class TranslationClaudeModuleTest extends TestBase
{
    public function testDeclinesAMotionSectionWithoutCredentials(): void
    {
        $this->assertNull(TranslationClaudeModuleWithoutCredentials::fillEmptyMotionSectionContent(new Motion(), new MotionSection()));
    }

    public function testDeclinesAnAmendmentSectionWithoutCredentials(): void
    {
        $this->assertNull(TranslationClaudeModuleWithoutCredentials::fillEmptyAmendmentSectionContent(new Amendment(), new AmendmentSection()));
    }
}
