<?php

namespace tests\codeception\unit\models;

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
        $consultation = Consultation::findOne(1);

        $this->specify(
            'For first S',
            function () use ($consultation) {
                $this->assertEquals('S1', $consultation->getNextAvailableStatusString(3));
            }
        );

        $dummyMotion                 = new Motion();
        $dummyMotion->title          = 'Testmotion';
        $dummyMotion->status         = Motion::STATUS_SUBMITTED_UNSCREENED;
        $dummyMotion->titlePrefix    = 'S1';
        $dummyMotion->motionTypeId   = 3;
        $dummyMotion->consultationId = $consultation->id;
        $dummyMotion->cache          = '';
        $dummyMotion->save();
        $consultation->refresh();

        $this->specify(
            'For second S',
            function () use ($consultation) {
                $this->assertEquals('S2', $consultation->getNextAvailableStatusString(3));
            }
        );

        $dummyMotion                 = new Motion();
        $dummyMotion->title          = 'Testmotion';
        $dummyMotion->status         = Motion::STATUS_SUBMITTED_UNSCREENED;
        $dummyMotion->titlePrefix    = 'S2';
        $dummyMotion->motionTypeId   = 2;
        $dummyMotion->consultationId = $consultation->id;
        $dummyMotion->cache          = '';
        $dummyMotion->save();
        $consultation->refresh();

        $this->specify(
            'For second S',
            function () use ($consultation) {
                $this->assertEquals('S3', $consultation->getNextAvailableStatusString(3));
            }
        );

        $dummyMotion                 = new Motion();
        $dummyMotion->title          = 'Testmotion';
        $dummyMotion->status         = Motion::STATUS_SUBMITTED_UNSCREENED;
        $dummyMotion->titlePrefix    = 'S4';
        $dummyMotion->motionTypeId   = 3;
        $dummyMotion->consultationId = $consultation->id;
        $dummyMotion->cache          = '';
        $dummyMotion->save();
        $consultation->refresh();

        $this->specify(
            'For third S',
            function () use ($consultation) {
                $this->assertEquals('S5', $consultation->getNextAvailableStatusString(3));
            }
        );

        $this->specify(
            'For first R',
            function () use ($consultation) {
                $this->assertEquals('R1', $consultation->getNextAvailableStatusString(2));
            }
        );
    }
}
