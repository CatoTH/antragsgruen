<?php

namespace Tests\Unit;

use app\components\DebateTools;
use app\models\db\{Consultation, DebateItem};
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class DebateToolsTest extends DBTestBase
{
    public function testStartDebateSwitchesToOtherMotion(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        // The fixture has an open debate on motion 2
        $previous = DebateItem::getCurrentForConsultation($consultation);
        $this->assertNotNull($previous);
        $this->assertSame(2, $previous->motionId);

        $motion = $consultation->getMotion(3);
        $started = DebateTools::startDebate($consultation, $motion);

        $this->assertNotSame($previous->id, $started->id);
        $this->assertSame(3, $started->motionId);
        $this->assertNull($started->amendmentId);
        $this->assertNull($started->agendaItemId);
        $this->assertNull($started->dateStopped);

        $previous->refresh();
        $this->assertNotNull($previous->dateStopped);

        $current = DebateItem::getCurrentForConsultation($consultation);
        $this->assertSame((int)$started->id, $current->id);
    }

    public function testStartDebateOnAlreadyDebatedItemIsNoop(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        $previous = DebateItem::getCurrentForConsultation($consultation);
        $motion = $consultation->getMotion($previous->motionId);

        $started = DebateTools::startDebate($consultation, $motion);

        $this->assertSame($previous->id, $started->id);
        $this->assertNull($started->dateStopped);
        $this->assertSame(1, (int)DebateItem::find()->where(['consultationId' => $consultation->id])->count());
    }

    public function testStartDebateOnAmendment(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        $amendment = $consultation->getAmendment(1);
        $started = DebateTools::startDebate($consultation, $amendment);

        $this->assertNull($started->motionId);
        $this->assertSame(1, $started->amendmentId);
        $this->assertNull($started->dateStopped);

        $current = DebateItem::getCurrentForConsultation($consultation);
        $this->assertSame((int)$started->id, $current->id);
    }

    public function testStartDebateOnAgendaItemWithoutPreviousDebate(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(3);

        $this->assertNull(DebateItem::getCurrentForConsultation($consultation));

        $agendaItem = $consultation->getAgendaItem(1);
        $started = DebateTools::startDebate($consultation, $agendaItem);

        $this->assertNull($started->motionId);
        $this->assertNull($started->amendmentId);
        $this->assertSame(1, $started->agendaItemId);
        $this->assertNull($started->dateStopped);
    }

    public function testStartFreeTextDebate(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        // The fixture has an open debate on motion 2, which should be ended
        $previous = DebateItem::getCurrentForConsultation($consultation);
        $this->assertSame(2, $previous->motionId);

        $started = DebateTools::startFreeTextDebate($consultation, 'General debate on the budget');

        $this->assertNull($started->motionId);
        $this->assertNull($started->amendmentId);
        $this->assertNull($started->agendaItemId);
        $this->assertSame('General debate on the budget', $started->freeText);
        $this->assertNull($started->dateStopped);

        $previous->refresh();
        $this->assertNotNull($previous->dateStopped);

        $current = DebateItem::getCurrentForConsultation($consultation);
        $this->assertSame((int)$started->id, $current->id);
    }

    public function testFreeTextDebateUsesGenericSpeechQueue(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        $debate = DebateTools::startFreeTextDebate($consultation, 'General debate');

        $queue = DebateTools::getOrCreateSpeechQueue($debate);
        // Free-text debates share the generic fallback queue, not assigned to any item
        $this->assertNull($queue->motionId);
        $this->assertNull($queue->amendmentId);
        $this->assertNull($queue->agendaItemId);

        // A second lookup resolves to the same generic queue rather than creating another one
        $again = DebateTools::getOrCreateSpeechQueue(DebateItem::getCurrentForConsultation($consultation));
        $this->assertSame($queue->id, $again->id);
    }

    public function testUserStateExposesAmendmentSpeechQueue(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        $amendment = $consultation->getAmendment(1);
        $debate = DebateTools::startDebate($consultation, $amendment);

        // Assign a speaking list to the debated amendment, as the admin widget's speech tab does
        $queue = DebateTools::getOrCreateSpeechQueue($debate);
        $this->assertSame(1, $queue->amendmentId);

        // The user-facing DebateState must expose that queue so the inline widget can load it
        $consultation->refresh();
        $state = \app\models\api\debate\DebateState::fromConsultation($consultation);
        $this->assertNotNull($state->current);
        $this->assertSame($queue->id, $state->current->speechQueueId);
    }

    public function testEndDebate(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        DebateTools::endDebate($consultation);

        $this->assertNull(DebateItem::getCurrentForConsultation($consultation));
    }
}
