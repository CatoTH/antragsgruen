<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\models\motionTypeTemplates\Motion as MotionTemplate;
use app\models\sectionTypes\ISectionType;
use app\models\settings\MotionSection as MotionSectionSettings;
use app\plugins\translation_claude\{ClaudeClient, SectionTranslator};
use Codeception\Attribute\Group;
use app\models\db\{Amendment, AmendmentSection, Consultation, ConsultationMotionType, ConsultationSettingsMotionSection, Motion, MotionSection};
use Tests\Support\Helper\{DBTestBase, RecordingClaudeClient};

/**
 * Covers plugins/translation_claude/SectionTranslator.php: finding the right source section to
 * translate from (preferring the primary language), and - for amendments - gathering all three
 * fragments the prompt needs (original motion text, changed amendment text, existing motion
 * translation). Uses a recording ClaudeClient test double instead of the real one, so the assertions
 * can inspect exactly what would have been sent to Claude without making network calls or needing
 * credentials. DB-backed for the same reason as MotionSectionLanguageFilterTest.
 */
#[Group('database')]
class TranslationClaudeSectionTranslatorTest extends DBTestBase
{
    private static function createSectionType(int $id, string $language, string $grouping): ConsultationSettingsMotionSection
    {
        $section                = new ConsultationSettingsMotionSection();
        $section->id            = $id;
        $section->title         = 'Text';
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->required      = ConsultationSettingsMotionSection::REQUIRED_NO;
        $section->position      = 0;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_NONE;
        $section->hasAmendments = 1;
        $section->positionRight = 0;

        $settings                   = $section->getSettingsObj();
        $settings->language         = $language;
        $settings->languageGrouping = $grouping;
        $section->setSettingsObj($settings);

        return $section;
    }

    /** Two grouped sections: $deId in German, $enId in English (both consultation 1's primary is de). */
    private static function createMotionType(int $deId, int $enId): ConsultationMotionType
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);
        $motionType   = MotionTemplate::doCreateMotionType($consultation);
        $motionType->link('motionSections', self::createSectionType($deId, 'de', 'text'));
        $motionType->link('motionSections', self::createSectionType($enId, 'en', 'text'));

        return $motionType;
    }

    private static function createMotion(ConsultationMotionType $motionType, int $deId, string $deText, int $enId, string $enText): Motion
    {
        $motion                          = new Motion();
        $motion->motionTypeId             = $motionType->id;
        $motion->consultationId           = $motionType->consultationId;
        $motion->title                    = '';
        $motion->titlePrefix              = '';
        $motion->version                  = Motion::VERSION_DEFAULT;
        $motion->status                   = Motion::STATUS_DRAFT;
        $motion->dateCreation             = date('Y-m-d H:i:s');
        $motion->dateContentModification  = date('Y-m-d H:i:s');
        $motion->cache                    = '';
        $motion->save();

        $de = MotionSection::createEmpty($deId, MotionSectionSettings::PUBLIC_YES, $motion->id);
        $de->setData($deText);
        $de->save();

        $en = MotionSection::createEmpty($enId, MotionSectionSettings::PUBLIC_YES, $motion->id);
        $en->setData($enText);
        $en->save();

        return $motion;
    }

    private static function createAmendment(Motion $motion): Amendment
    {
        $amendment                          = new Amendment();
        $amendment->motionId                = $motion->id;
        $amendment->status                  = Amendment::STATUS_DRAFT;
        $amendment->statusString            = '';
        $amendment->titlePrefix             = '';
        $amendment->changeEditorial         = '';
        $amendment->changeText              = '';
        $amendment->changeExplanation       = '';
        $amendment->cache                   = '';
        $amendment->dateCreation            = date('Y-m-d H:i:s');
        $amendment->dateContentModification = date('Y-m-d H:i:s');
        $amendment->save();

        return $amendment;
    }

    private static function createAmendmentSection(Amendment $amendment, MotionSection $original, string $data): AmendmentSection
    {
        $section                 = new AmendmentSection();
        $section->sectionId      = $original->sectionId;
        $section->amendmentId    = $amendment->id;
        $section->public         = MotionSectionSettings::PUBLIC_YES;
        $section->cache          = '';
        $section->setOriginalMotionSection($original);
        $section->setData($data);
        $section->dataRaw = $data;
        $section->save();

        return $section;
    }

    public function testTranslatesFromThePrimaryLanguage(): void
    {
        $motionType = self::createMotionType(2400, 2401);
        $motion     = self::createMotion($motionType, 2400, '<p>Klimaschutz jetzt</p>', 2401, '');

        $client = new RecordingClaudeClient('<p>Climate protection now</p>');
        $translator = new SectionTranslator(...self::translatorArgs($client));

        $motion->refresh();
        $enSection = self::findSection($motion->getActiveSections(), 2401);

        $result = $translator->translateMotionSection($motion, $enSection);

        $this->assertSame('<p>Climate protection now</p>', $result);
        $this->assertSame('<p>Klimaschutz jetzt</p>', $client->lastUserMessage);
        $this->assertStringContainsString('Deutsch', (string) $client->lastSystemPrompt);
        $this->assertStringContainsString('English', (string) $client->lastSystemPrompt);
    }

    public function testReturnsNullWithoutALanguageGrouping(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);
        $motionType   = MotionTemplate::doCreateMotionType($consultation);
        $section      = new ConsultationSettingsMotionSection();
        $section->id            = 2402;
        $section->title         = 'Text';
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->required      = ConsultationSettingsMotionSection::REQUIRED_NO;
        $section->position      = 0;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_NONE;
        $section->hasAmendments = 1;
        $section->positionRight = 0;
        $motionType->link('motionSections', $section);

        $motion = new Motion();
        $motion->motionTypeId            = $motionType->id;
        $motion->consultationId          = $motionType->consultationId;
        $motion->title                   = '';
        $motion->titlePrefix             = '';
        $motion->version                 = Motion::VERSION_DEFAULT;
        $motion->status                  = Motion::STATUS_DRAFT;
        $motion->dateCreation            = date('Y-m-d H:i:s');
        $motion->dateContentModification = date('Y-m-d H:i:s');
        $motion->cache                   = '';
        $motion->save();

        $motionSection = MotionSection::createEmpty(2402, MotionSectionSettings::PUBLIC_YES, $motion->id);
        $motionSection->setData('');
        $motionSection->save();

        $client = new RecordingClaudeClient('irrelevant');
        $translator = new SectionTranslator(...self::translatorArgs($client));

        $motion->refresh();
        $result = $translator->translateMotionSection($motion, $motion->getActiveSections()[0]);

        $this->assertNull($result);
        $this->assertNull($client->lastUserMessage);
    }

    public function testReturnsNullWithoutASourceSectionWithContent(): void
    {
        $motionType = self::createMotionType(2403, 2404);
        $motion     = self::createMotion($motionType, 2403, '', 2404, '');

        $client = new RecordingClaudeClient('irrelevant');
        $translator = new SectionTranslator(...self::translatorArgs($client));

        $motion->refresh();
        $enSection = self::findSection($motion->getActiveSections(), 2404);

        $this->assertNull($translator->translateMotionSection($motion, $enSection));
        $this->assertNull($client->lastUserMessage);
    }

    public function testAmendmentTranslationGathersAllThreeFragments(): void
    {
        $motionType = self::createMotionType(2405, 2406);
        $motion     = self::createMotion($motionType, 2405, '<p>Klimaschutz jetzt</p>', 2406, '<p>Climate protection now</p>');
        $amendment  = self::createAmendment($motion);

        $motion->refresh();
        $deOriginal = self::findSection($motion->getActiveSections(), 2405);
        $enOriginal = self::findSection($motion->getActiveSections(), 2406);

        // The submitter changed the German amendment text; English is still untouched (unchanged
        // from its original), exactly like a real amendment for a language the submitter didn't
        // touch.
        self::createAmendmentSection($amendment, $deOriginal, '<p>Klimaschutz sofort</p>');
        $enAmendmentSection = self::createAmendmentSection($amendment, $enOriginal, $enOriginal->getData());

        $client = new RecordingClaudeClient('<p>Climate protection immediately</p>');
        $translator = new SectionTranslator(...self::translatorArgs($client));

        $amendment->refresh();
        $enAmendmentSectionFresh = self::findSection($amendment->getActiveSections(), 2406);

        $result = $translator->translateAmendmentSection($amendment, $enAmendmentSectionFresh);

        $this->assertSame('<p>Climate protection immediately</p>', $result);
        $this->assertStringContainsString('<p>Klimaschutz jetzt</p>', (string) $client->lastUserMessage);
        $this->assertStringContainsString('<p>Klimaschutz sofort</p>', (string) $client->lastUserMessage);
        $this->assertStringContainsString('<p>Climate protection now</p>', (string) $client->lastUserMessage);
        $this->assertStringContainsString('reuses the exact wording', (string) $client->lastSystemPrompt);
    }

    public function testAmendmentTranslationReturnsNullWithoutAChangedSibling(): void
    {
        $motionType = self::createMotionType(2407, 2408);
        $motion     = self::createMotion($motionType, 2407, '<p>Klimaschutz jetzt</p>', 2408, '<p>Climate protection now</p>');
        $amendment  = self::createAmendment($motion);

        $motion->refresh();
        $deOriginal = self::findSection($motion->getActiveSections(), 2407);
        $enOriginal = self::findSection($motion->getActiveSections(), 2408);

        // Neither language was actually changed by this amendment.
        self::createAmendmentSection($amendment, $deOriginal, $deOriginal->getData());
        self::createAmendmentSection($amendment, $enOriginal, $enOriginal->getData());

        $client = new RecordingClaudeClient('irrelevant');
        $translator = new SectionTranslator(...self::translatorArgs($client));

        $amendment->refresh();
        $enAmendmentSection = self::findSection($amendment->getActiveSections(), 2408);

        $this->assertNull($translator->translateAmendmentSection($amendment, $enAmendmentSection));
        $this->assertNull($client->lastUserMessage);
    }

    /**
     * @param MotionSection[]|AmendmentSection[] $sections
     */
    private static function findSection(array $sections, int $sectionId): MotionSection|AmendmentSection
    {
        foreach ($sections as $section) {
            if ($section->sectionId === $sectionId) {
                return $section;
            }
        }
        throw new \RuntimeException('Section not found: ' . $sectionId);
    }

    /**
     * SectionTranslator's constructor takes a Credentials (unused by the recording test double, but
     * required by the real ClaudeClient's constructor signature) plus the injectable client.
     *
     * @return array{0: \app\plugins\translation_claude\Credentials, 1: ClaudeClient}
     */
    private static function translatorArgs(ClaudeClient $client): array
    {
        $path = sys_get_temp_dir() . '/translation_claude_translator_test_' . uniqid() . '.json';
        file_put_contents($path, json_encode(['apiKey' => 'sk-ant-test'], JSON_THROW_ON_ERROR));
        $credentials = \app\plugins\translation_claude\Credentials::load($path);
        unlink($path);

        return [$credentials, $client];
    }
}
