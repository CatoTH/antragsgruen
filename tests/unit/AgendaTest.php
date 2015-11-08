<?php

namespace unit;

use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use Codeception\Specify;

class AgendaTest extends DBTestBase
{
    /**
     */
    public function testShownCodes()
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