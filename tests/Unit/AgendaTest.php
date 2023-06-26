<?php

namespace Tests\Unit;

use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class AgendaTest extends DBTestBase
{
    /**
     */
    public function testSortingChildItems(): void
    {
        /** @var ConsultationAgendaItem $item6 */
        $item6       = ConsultationAgendaItem::findOne(6);
        $item6->code = 'V';
        $item6->save();

        $newItem                 = new ConsultationAgendaItem();
        $newItem->consultationId = $item6->consultationId;
        $newItem->parentItemId   = $item6->id;
        $newItem->position       = 1;
        $newItem->title          = 'New Item 1';
        $newItem->code           = '#';
        $newItem->save();
        $item6->refresh();

        $newItem2                 = new ConsultationAgendaItem();
        $newItem2->consultationId = $item6->consultationId;
        $newItem2->parentItemId   = $item6->id;
        $newItem2->position       = 3;
        $newItem2->title          = 'New Item 2';
        $newItem2->code           = '#';
        $newItem2->save();

        $newItem3                 = new ConsultationAgendaItem();
        $newItem3->consultationId = $item6->consultationId;
        $newItem3->parentItemId   = $item6->id;
        $newItem3->position       = 2;
        $newItem3->title          = 'New Item 3';
        $newItem3->code           = '#';
        $newItem3->save();

        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(3);
        $pos1 = $pos2 = $pos3 = null;
        foreach (ConsultationAgendaItem::getSortedFromConsultation($consultation) as $i => $item) {
            if ($item->title==='New Item 1') {
                $pos1 = $i;
            }
            if ($item->title==='New Item 2') {
                $pos2 = $i;
            }
            if ($item->title==='New Item 3') {
                $pos3 = $i;
            }
        }

        $this->assertLessThan($pos3, $pos1);
        $this->assertLessThan($pos2, $pos3);
    }

    /**
     */
    public function testNonNumericAgenda(): void
    {
        /** @var ConsultationAgendaItem $item6 */
        $item6       = ConsultationAgendaItem::findOne(6);
        $item6->code = 'V';
        $item6->save();
        /** @var ConsultationAgendaItem $item7 */
        $item7 = ConsultationAgendaItem::findOne(7);

        $newItem                 = new ConsultationAgendaItem();
        $newItem->consultationId = $item6->consultationId;
        $newItem->parentItemId   = $item6->id;
        $newItem->position       = 0;
        $newItem->title          = 'New Item';
        $newItem->code           = '#';
        $newItem->save();
        $item6->refresh();

        $newItem2                 = new ConsultationAgendaItem();
        $newItem2->consultationId = $item7->consultationId;
        $newItem2->parentItemId   = $item7->id;
        $newItem2->position       = 0;
        $newItem2->title          = 'New Item';
        $newItem2->code           = '#';
        $newItem2->save();

        $newItem3                 = new ConsultationAgendaItem();
        $newItem3->consultationId = $newItem->consultationId;
        $newItem3->parentItemId   = $newItem->id;
        $newItem3->position       = 0;
        $newItem3->title          = 'New Item';
        $newItem3->code           = '#';
        $newItem3->save();

        $newItem4                 = new ConsultationAgendaItem();
        $newItem4->consultationId = $newItem->consultationId;
        $newItem4->parentItemId   = $newItem->id;
        $newItem4->position       = 1;
        $newItem4->title          = 'New Item2';
        $newItem4->code           = '#';
        $newItem4->save();

        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(3);
        $items        = ConsultationAgendaItem::getSortedFromConsultation($consultation);
        $item6        = $item7 = null;
        foreach ($items as $item) {
            if ($item->id === 6) {
                $item6 = $item;
            }
            if ($item->id === 7) {
                $item7 = $item;
            }
        }

        $this->assertEquals('V', $item6->getShownCode(true));
        $this->assertEquals('V', $item6->getShownCode(false));
        $this->assertEquals('W', $item7->getShownCode(true));
        $this->assertEquals('W', $item7->getShownCode(false));
        $this->assertEquals('1.', $newItem->getShownCode(false));
        $this->assertEquals('V.1.', $newItem->getShownCode(true));
        $this->assertEquals('1.', $newItem2->getShownCode(false));
        $this->assertEquals('W.1.', $newItem2->getShownCode(true));
        $this->assertEquals('2.', $newItem4->getShownCode(false));
        $this->assertEquals('V.1.2.', $newItem4->getShownCode(true));
    }

    /**
     */
    public function testShownCodes(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(3);
        $items        = ConsultationAgendaItem::getSortedFromConsultation($consultation);
        $this->assertEquals('0.', $items[0]->getShownCode(true));
        $this->assertEquals('0.', $items[0]->getShownCode(false));
        $this->assertEquals('1.2.', $items[3]->getShownCode(true));
        $this->assertEquals('2.', $items[3]->getShownCode(false));
        $this->assertEquals('3.', $items[6]->getShownCode(true));
        $this->assertEquals('3.', $items[6]->getShownCode(false));


        /** @var ConsultationAgendaItem $item */
        $item = ConsultationAgendaItem::findOne(4);
        $this->assertEquals('1.2.', $item->getShownCode(true));
        $this->assertEquals('2.', $item->getShownCode(false));

        $item = ConsultationAgendaItem::findOne(6);
        $this->assertEquals('2.', $item->getShownCode(true));
        $this->assertEquals('2.', $item->getShownCode(false));
    }
}
