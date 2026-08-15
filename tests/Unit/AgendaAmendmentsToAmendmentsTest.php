<?php

namespace Tests\Unit;

use app\models\db\{Amendment, ConsultationAgendaItem, IMotion, Motion};
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

/**
 * An amendment amending another amendment always belongs below the amendment it amends - both on an
 * agenda-less home page and on the agenda-based ones. It must never be listed at an agenda item itself,
 * even though it can well carry an agendaItemId of its own.
 */
#[Group('database')]
class AgendaAmendmentsToAmendmentsTest extends DBTestBase
{
    private ConsultationAgendaItem $agendaItem;
    private Amendment $amendment;
    private Amendment $amendingAmendment;

    private function createScenario(): void
    {
        /** @var Motion $motion */
        $motion = Motion::findOne(2);

        $this->agendaItem = new ConsultationAgendaItem();
        $this->agendaItem->consultationId = $motion->consultationId;
        $this->agendaItem->parentItemId = null;
        $this->agendaItem->position = 0;
        $this->agendaItem->title = 'Test agenda item';
        $this->agendaItem->code = '#';
        $this->assertTrue($this->agendaItem->save());

        $motion->agendaItemId = $this->agendaItem->id;
        $this->assertTrue($motion->save());

        /** @var Amendment $amendment */
        $amendment = Amendment::findOne(1);
        $amendment->agendaItemId = $this->agendaItem->id;
        $this->assertTrue($amendment->save());
        $this->amendment = $amendment;

        // The amendment amending it. It carries the same agendaItemId - which is what happens in practice,
        // as Amendment::getMyAgendaItem() falls back to the agenda item of the motion and editing an
        // amendment persists that fallback.
        $amendingAmendment = new Amendment();
        $amendingAmendment->setAttributes($amendment->getAttributes(), false);
        $amendingAmendment->id = null;
        $amendingAmendment->setIsNewRecord(true);
        $amendingAmendment->amendingAmendmentId = $amendment->id;
        $amendingAmendment->agendaItemId = $this->agendaItem->id;
        $amendingAmendment->titlePrefix = 'Ä1-1';
        $this->assertTrue($amendingAmendment->save());
        $this->amendingAmendment = $amendingAmendment;

        $motion->refresh();
        $this->agendaItem->refresh();
    }

    public function testIsShownAtAgendaItemDirectly(): void
    {
        $this->createScenario();

        $this->assertTrue($this->amendment->isShownAtAgendaItemDirectly());
        $this->assertFalse($this->amendingAmendment->isShownAtAgendaItemDirectly());

        // An amendment without an explicit agenda item is shown below its motion, not at an agenda item
        /** @var Amendment $other */
        $other = Amendment::findOne(3);
        $this->assertNull($other->agendaItemId);
        $this->assertFalse($other->isShownAtAgendaItemDirectly());
    }

    public function testAgendaItemDoesNotListAmendmentsToAmendments(): void
    {
        $this->createScenario();

        $listed = array_map(
            fn (IMotion $imotion) => get_class($imotion) . '#' . $imotion->id,
            $this->agendaItem->getMyIMotions()
        );

        $this->assertContains(Amendment::class . '#' . $this->amendment->id, $listed);
        $this->assertNotContains(Amendment::class . '#' . $this->amendingAmendment->id, $listed);
    }

    public function testAgendaOverviewDoesNotListAmendmentsToAmendments(): void
    {
        $this->createScenario();

        $listed = array_map(
            fn (IMotion $imotion) => get_class($imotion) . '#' . $imotion->id,
            $this->agendaItem->getIMotionsFromConsultation()
        );

        $this->assertContains(Amendment::class . '#' . $this->amendment->id, $listed);
        $this->assertNotContains(Amendment::class . '#' . $this->amendingAmendment->id, $listed);
    }

    /**
     * ...and it still has to show up below the amendment it amends, rather than disappearing entirely.
     */
    public function testAmendmentToAmendmentStaysNestedBelowItsParent(): void
    {
        $this->createScenario();

        $nested = array_map(fn (Amendment $amendment) => $amendment->id, $this->amendment->amendingAmendments);
        $this->assertContains($this->amendingAmendment->id, $nested);
    }
}
