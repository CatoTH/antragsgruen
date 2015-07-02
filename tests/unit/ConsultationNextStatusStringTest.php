<?php

namespace unit;

use Codeception\Util\Autoload;
use app\models\db\Consultation;
use app\models\db\Motion;
use Yii;
use Codeception\Specify;

class ConsultationNextStatusStringTest extends DBTestBase
{
    use Specify;

    /**
     *
     */
    public function testStatusString()
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(2);

        // 'For first A',
        $this->assertEquals('A1', $consultation->getNextAvailableStatusString(3));


        // 'For second A
        $dummyMotion                 = new Motion();
        $dummyMotion->title          = 'Testmotion';
        $dummyMotion->status         = Motion::STATUS_SUBMITTED_UNSCREENED;
        $dummyMotion->titlePrefix    = 'A1';
        $dummyMotion->motionTypeId   = 3;
        $dummyMotion->consultationId = $consultation->id;
        $dummyMotion->cache          = '';
        $dummyMotion->save();
        $consultation->refresh();

        $this->assertEquals('A2', $consultation->getNextAvailableStatusString(3));


        // 'For second B'

        $dummyMotion                 = new Motion();
        $dummyMotion->title          = 'Testmotion';
        $dummyMotion->status         = Motion::STATUS_SUBMITTED_UNSCREENED;
        $dummyMotion->titlePrefix    = 'B1';
        $dummyMotion->motionTypeId   = 4;
        $dummyMotion->consultationId = $consultation->id;
        $dummyMotion->cache          = '';
        $dummyMotion->save();
        $consultation->refresh();

        $this->assertEquals('B2', $consultation->getNextAvailableStatusString(4));


        // 'For third S'

        $dummyMotion                 = new Motion();
        $dummyMotion->title          = 'Testmotion';
        $dummyMotion->status         = Motion::STATUS_SUBMITTED_UNSCREENED;
        $dummyMotion->titlePrefix    = 'A4';
        $dummyMotion->motionTypeId   = 3;
        $dummyMotion->consultationId = $consultation->id;
        $dummyMotion->cache          = '';
        $dummyMotion->save();
        $consultation->refresh();

        $this->assertEquals('A5', $consultation->getNextAvailableStatusString(3));


        /*
        $this->specify(
            'For first R',
            function () use ($consultation) {
                $this->assertEquals('R1', $consultation->getNextAvailableStatusString(4));
            }
        );
        */
    }
}
