<?php

namespace Tests\Unit;

use app\components\Tools;
use app\models\api\debate\{DebateItemTargetType, DebateSelectableItem, DebateSelectables};
use app\models\db\Consultation;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class DebateSelectablesTest extends DBTestBase
{
    public function testMotionsAndAmendments(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        $selectables = DebateSelectables::fromConsultation($consultation);

        $motionIds = array_map(fn (DebateSelectableItem $item) => $item->targetId, $selectables->motions);
        $this->assertContains(2, $motionIds);
        $this->assertContains(3, $motionIds);

        $motion2 = $selectables->motions[array_search(2, $motionIds, true)];
        $this->assertSame(DebateItemTargetType::MOTION, $motion2->targetType);
        $this->assertSame('O’zapft is!', $motion2->title);
        $this->assertStringContainsString('A2', $motion2->titleWithPrefix);
        $this->assertNotNull($motion2->initiatorsHtml);

        $amendmentIds = array_map(fn (DebateSelectableItem $item) => $item->targetId, $selectables->amendments);
        $this->assertContains(1, $amendmentIds);
        $amendment1 = $selectables->amendments[array_search(1, $amendmentIds, true)];
        $this->assertSame(DebateItemTargetType::AMENDMENT, $amendment1->targetType);
        $this->assertStringContainsString('Ä1', $amendment1->titleWithPrefix);

        // Consultation 1 has no agenda items in the fixture
        $this->assertSame([], $selectables->agendaItems);

        $data = json_decode(Tools::getSerializer()->serialize($selectables, 'json'), true);
        $this->assertSame(['motions', 'amendments', 'agenda_items'], array_keys($data));
        $this->assertSame('motion', $data['motions'][0]['target_type']);
        $this->assertArrayHasKey('title_with_prefix', $data['motions'][0]);
        $this->assertArrayHasKey('initiators_html', $data['motions'][0]);
    }

    public function testAgendaItems(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(3);

        $selectables = DebateSelectables::fromConsultation($consultation);

        $this->assertNotEmpty($selectables->agendaItems);
        $first = $selectables->agendaItems[0];
        $this->assertSame(DebateItemTargetType::AGENDA_ITEM, $first->targetType);
        $this->assertSame(1, $first->targetId);
        $this->assertSame('Tagesordnung', $first->title);
        $this->assertStringContainsString('0.', $first->titleWithPrefix);
        $this->assertNull($first->initiatorsHtml);
    }
}
