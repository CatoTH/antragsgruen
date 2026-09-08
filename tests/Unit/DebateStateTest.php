<?php

namespace Tests\Unit;

use app\components\{DebateTools, LocalizedStringNormalizer, Tools};
use app\models\api\debate\{DebateItemTargetType, DebateState};
use app\models\db\{Consultation, DebateItem, Site};
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class DebateStateTest extends DBTestBase
{
    /** Consultation 1's wordingBase is "de-parteitag", so "de" is the primary language. */
    private static function setSiteSupportedLanguages(array $languages): Consultation
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);
        /** @var Site $site */
        $site = $consultation->site;

        $settings = $site->getSettings();
        $settings->supportedLanguages = $languages;
        $site->setSettings($settings);
        $site->save();

        $consultation->refresh();

        return $consultation;
    }

    /**
     * The speaking list's title is built from a translated string ("Speaking list for %TITLE%"),
     * which is what makes it differ between the languages of a live event.
     */
    private static function attachSpeechQueueToCurrentDebate(Consultation $consultation): Consultation
    {
        DebateTools::getOrCreateSpeechQueue(DebateItem::getCurrentForConsultation($consultation));
        $consultation->refresh();

        return $consultation;
    }

    public function testCurrentDebateOnMotion(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        $state = DebateState::fromConsultation($consultation);

        $this->assertNotNull($state->current);
        $this->assertSame(DebateItemTargetType::MOTION, $state->current->targetType);
        $this->assertSame(2, $state->current->targetId);
        $this->assertSame('O’zapft is!', $state->current->title->get());
        $this->assertStringContainsString('A2', $state->current->titleWithPrefix->get());
        $this->assertNotNull($state->current->initiatorsHtml);
        $this->assertStringStartsWith('2015-03-30T10:00:00', $state->current->dateStarted);
        $this->assertNull($state->current->votingBlock);

        $data = json_decode(Tools::getSerializer()->serialize($state, 'json'), true);
        $this->assertSame('motion', $data['current']['target_type']);
        $this->assertSame(2, $data['current']['target_id']);
        $this->assertSame('O’zapft is!', $data['current']['title']);
        $this->assertArrayHasKey('url_html', $data['current']);
        $this->assertArrayHasKey('speech_queue', $data['current']);
        $this->assertArrayHasKey('voting_block', $data['current']);
    }

    public function testNoCurrentDebate(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(4);

        $state = DebateState::fromConsultation($consultation);

        $this->assertNull($state->current);

        $data = json_decode(Tools::getSerializer()->serialize($state, 'json'), true);
        $this->assertSame(['current' => null], $data);
    }

    /**
     * A REST response is read by one user in one language, so it holds plain strings - both on a
     * single-language and on a multi-language site.
     */
    public function testRestResponseHoldsPlainStringsOfTheReaderLanguage(): void
    {
        $consultation = self::attachSpeechQueueToCurrentDebate(self::setSiteSupportedLanguages(['de', 'en']));

        $state = DebateState::fromConsultation($consultation);
        $data = json_decode(Tools::getSerializer()->serialize($state, 'json'), true);

        $this->assertIsString($data['current']['title']);
        $this->assertIsString($data['current']['title_with_prefix']);
        $this->assertIsString($data['current']['speech_queue']['title']);
    }

    /**
     * A live event is pushed to all readers of the consultation at once, so it holds every language
     * of the consultation; the Live server picks the one matching each subscriber.
     */
    public function testLiveEventHoldsEveryLanguageOfTheConsultation(): void
    {
        $consultation = self::attachSpeechQueueToCurrentDebate(self::setSiteSupportedLanguages(['de', 'en']));

        $state = DebateState::fromConsultation($consultation);
        $data = json_decode(Tools::getSerializer()->serialize($state, 'json', [
            LocalizedStringNormalizer::CONTEXT_ALL_LANGUAGES => true,
        ]), true);

        $this->assertSame(['de', 'en'], array_keys($data['current']['title']));
        $this->assertSame('O’zapft is!', $data['current']['title']['de']);
        $this->assertSame('O’zapft is!', $data['current']['title']['en']);

        // The wording around the motion title is translated, so the two languages have to differ
        $speechQueueTitles = $data['current']['speech_queue']['title'];
        $this->assertSame(['de', 'en'], array_keys($speechQueueTitles));
        $this->assertStringContainsString('A2', $speechQueueTitles['de']);
        $this->assertStringContainsString('A2', $speechQueueTitles['en']);
        $this->assertNotSame($speechQueueTitles['de'], $speechQueueTitles['en']);
    }

    /**
     * On a single-language site, a live event carries exactly one language - no matter which
     * language the user triggering it happens to be browsing the site in.
     */
    public function testLiveEventOnSingleLanguageSiteHoldsOnlyThePrimaryLanguage(): void
    {
        $consultation = self::setSiteSupportedLanguages([]);

        $state = DebateState::fromConsultation($consultation);
        $data = json_decode(Tools::getSerializer()->serialize($state, 'json', [
            LocalizedStringNormalizer::CONTEXT_ALL_LANGUAGES => true,
        ]), true);

        $this->assertSame(['de'], array_keys($data['current']['title']));
    }
}
