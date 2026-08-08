<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\{LanguageTools, UrlHelper};
use app\models\settings\AntragsgruenApp;
use Tests\Support\Helper\TestBase;
use Yii;

class LanguageToolsTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();

        UrlHelper::setCurrentSite(null);
        UrlHelper::setCurrentConsultation(null);
        LanguageTools::resetRequestCache();
    }

    protected function tearDown(): void
    {
        UrlHelper::setCurrentSite(null);
        UrlHelper::setCurrentConsultation(null);
        LanguageTools::resetRequestCache();

        parent::tearDown();
    }

    public function testSupportedLanguagesFilterDropsUnknownCodes(): void
    {
        $this->assertSame(['de', 'nl'], LanguageTools::filterSupportedLanguages(['de', 'klingon', 'nl']));
        $this->assertSame([], LanguageTools::filterSupportedLanguages([]));
    }

    public function testSupportedLanguagesFilterKeepsTheConfiguredOrder(): void
    {
        $this->assertSame(['nl', 'de'], LanguageTools::filterSupportedLanguages(['nl', 'de']));
    }

    public function testWithoutASiteNoLanguagesAreSupported(): void
    {
        $this->assertSame([], LanguageTools::getSupportedLanguages());
        $this->assertFalse(LanguageTools::isMultiLanguageSite());
    }

    public function testPrimaryLanguageIsDerivedFromTheWordingBase(): void
    {
        $this->assertSame('de', LanguageTools::resolvePrimaryLanguage('de-parteitag'));
        $this->assertSame('de', LanguageTools::resolvePrimaryLanguage('de'));
        $this->assertSame('en', LanguageTools::resolvePrimaryLanguage('en-gb'));
        $this->assertSame('nl', LanguageTools::resolvePrimaryLanguage('nl'));
    }

    public function testPrimaryLanguageUsesTheFirstOfSeveralWordingVariants(): void
    {
        $this->assertSame('de', LanguageTools::resolvePrimaryLanguage('de-parteitag,de-bdk'));
    }

    public function testPrimaryLanguageFallsBackToTheInstallationLanguage(): void
    {
        /** @var AntragsgruenApp $params */
        $params = Yii::$app->params;
        $params->baseLanguage = 'nl';

        $this->assertSame('nl', LanguageTools::resolvePrimaryLanguage(null));
        $this->assertSame('nl', LanguageTools::resolvePrimaryLanguage(''));
        $this->assertSame('nl', LanguageTools::resolvePrimaryLanguage('klingon'));
    }

    public function testPrimaryLanguageFallsBackToEnglish(): void
    {
        /** @var AntragsgruenApp $params */
        $params = Yii::$app->params;
        $params->baseLanguage = 'klingon';

        $this->assertSame('en', LanguageTools::resolvePrimaryLanguage(null));
    }

    public function testBrowserLanguageMatchingFollowsTheBrowsersOrder(): void
    {
        $this->assertSame('en', LanguageTools::matchBrowserLanguage(['en-GB', 'de'], ['de', 'en']));
        $this->assertSame('de', LanguageTools::matchBrowserLanguage(['de-AT', 'en'], ['de', 'en']));
    }

    public function testBrowserLanguageMatchingSkipsUnsupportedLanguages(): void
    {
        $this->assertSame('nl', LanguageTools::matchBrowserLanguage(['fr', 'nl-BE', 'de'], ['de', 'nl']));
    }

    public function testBrowserLanguageMatchingNormalizesSeparatorsAndCase(): void
    {
        $this->assertSame('de', LanguageTools::matchBrowserLanguage(['DE_at'], ['de', 'en']));
    }

    public function testBrowserLanguageMatchingReturnsNullWithoutAMatch(): void
    {
        $this->assertNull(LanguageTools::matchBrowserLanguage(['fr', 'it'], ['de', 'en']));
        $this->assertNull(LanguageTools::matchBrowserLanguage([], ['de', 'en']));
    }

    public function testLanguageNamesAreResolved(): void
    {
        $this->assertSame('Deutsch', LanguageTools::getLanguageName('de'));
        $this->assertSame('English', LanguageTools::getLanguageName('en'));
        $this->assertSame('klingon', LanguageTools::getLanguageName('klingon'));
    }

    public function testLanguageIconsAreResolvedFromTheTargetLanguagesOwnMessages(): void
    {
        // Each language's icon comes from that language's own messages/<language>/base.php, not the
        // one this test (or the current app) happens to be running in.
        $this->assertSame('🇩🇪', LanguageTools::getLanguageIcon('de'));
        $this->assertSame('🇬🇧', LanguageTools::getLanguageIcon('en'));
        $this->assertSame('🇫🇷', LanguageTools::getLanguageIcon('fr'));
        $this->assertSame('🇳🇱', LanguageTools::getLanguageIcon('nl'));
        $this->assertSame('🇲🇪', LanguageTools::getLanguageIcon('me'));
    }
}
