<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\{LocalizedStringNormalizer, Tools};
use app\models\api\SpeechQueue as SpeechQueueApi;
use app\models\db\{Consultation, Site, SpeechQueue};
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

/**
 * The speaking list payload is both returned by the REST API and pushed to all readers of a
 * consultation at once. Its one reader-dependent string - the name of the list that activating this
 * one would deactivate - therefore has to be a LocalizedString, serialized as a plain string for the
 * former and with one entry per language for the latter. See docs/technical/live-data.md.
 */
#[Group('database')]
class SpeechQueueApiTest extends DBTestBase
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
     * A queue that is not active yet, while another one is - the constellation otherActiveName
     * describes.
     */
    private static function createInactiveQueueBesidesAnActiveOne(Consultation $consultation): SpeechQueue
    {
        $active = SpeechQueue::createWithSubqueues($consultation, true);
        $active->motionId = $consultation->getMotion(2)->id;
        $active->save();

        $inactive = SpeechQueue::createWithSubqueues($consultation, false);
        $consultation->refresh();

        return self::findQueue($consultation, $inactive->id);
    }

    /**
     * The queue as the (refreshed) consultation knows it - fromEntity() looks at its sibling queues
     * through that relation.
     */
    private static function findQueue(Consultation $consultation, int $queueId): SpeechQueue
    {
        foreach ($consultation->speechQueues as $queue) {
            if ($queue->id === $queueId) {
                return $queue;
            }
        }

        throw new \RuntimeException('Speech queue ' . $queueId . ' not found');
    }

    public function testRestResponseHoldsThePlainStringOfTheReaderLanguage(): void
    {
        $consultation = self::setSiteSupportedLanguages(['de', 'en']);
        $queue = self::createInactiveQueueBesidesAnActiveOne($consultation);

        $data = json_decode(Tools::getSerializer()->serialize(SpeechQueueApi::fromEntity($queue), 'json'), true);

        $this->assertIsString($data['other_active_name']);
        $this->assertStringContainsString('A2', $data['other_active_name']);
    }

    public function testLiveEventHoldsEveryLanguageOfTheConsultation(): void
    {
        $consultation = self::setSiteSupportedLanguages(['de', 'en']);
        $queue = self::createInactiveQueueBesidesAnActiveOne($consultation);

        $data = json_decode(Tools::getSerializer()->serialize(SpeechQueueApi::fromEntity($queue), 'json', [
            LocalizedStringNormalizer::CONTEXT_ALL_LANGUAGES => true,
        ]), true);

        $this->assertSame(['de', 'en'], array_keys($data['other_active_name']));
        $this->assertNotSame($data['other_active_name']['de'], $data['other_active_name']['en']);
    }

    /**
     * On a single-language site the field carries exactly one language - which is what the Live
     * server delivers to everyone, no matter which language they state.
     */
    public function testLiveEventOnSingleLanguageSiteHoldsOnlyThePrimaryLanguage(): void
    {
        $consultation = self::setSiteSupportedLanguages([]);
        $queue = self::createInactiveQueueBesidesAnActiveOne($consultation);

        $data = json_decode(Tools::getSerializer()->serialize(SpeechQueueApi::fromEntity($queue), 'json', [
            LocalizedStringNormalizer::CONTEXT_ALL_LANGUAGES => true,
        ]), true);

        $this->assertSame(['de'], array_keys($data['other_active_name']));
    }

    /**
     * Subqueue names are backed by a single database column so far, so they resolve to the same text
     * in every language - but they travel as a localized value, so a per-language name needs no
     * change to the API or the Live server. Same for the copy of the name on every slot.
     */
    public function testSubqueueNamesAreLocalized(): void
    {
        $consultation = self::setSiteSupportedLanguages(['de', 'en']);
        $queue = SpeechQueue::createWithSubqueues($consultation, false);
        $consultation->refresh();
        $dto = SpeechQueueApi::fromEntity(self::findQueue($consultation, $queue->id));

        $rest = json_decode(Tools::getSerializer()->serialize($dto, 'json'), true);
        $this->assertIsString($rest['subqueues'][0]['name']);

        $live = json_decode(Tools::getSerializer()->serialize($dto, 'json', [
            LocalizedStringNormalizer::CONTEXT_ALL_LANGUAGES => true,
        ]), true);
        $this->assertSame(['de', 'en'], array_keys($live['subqueues'][0]['name']));
        $this->assertSame($rest['subqueues'][0]['name'], $live['subqueues'][0]['name']['de']);
        $this->assertSame($live['subqueues'][0]['name']['de'], $live['subqueues'][0]['name']['en']);
    }

    /**
     * Without another active list there is nothing to name, and the field stays null in both forms.
     */
    public function testNoOtherActiveQueue(): void
    {
        $consultation = self::setSiteSupportedLanguages(['de', 'en']);
        $queue = SpeechQueue::createWithSubqueues($consultation, false);
        $consultation->refresh();

        $dto = SpeechQueueApi::fromEntity(self::findQueue($consultation, $queue->id));
        $this->assertNull($dto->otherActiveName);

        $data = json_decode(Tools::getSerializer()->serialize($dto, 'json', [
            LocalizedStringNormalizer::CONTEXT_ALL_LANGUAGES => true,
        ]), true);
        $this->assertNull($data['other_active_name']);
    }
}
