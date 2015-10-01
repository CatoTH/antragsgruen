<?php

namespace unit;

use app\models\amendmentNumbering\GlobalCompact;
use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\db\Amendment;
use Codeception\Util\Autoload;
use app\models\db\Consultation;
use app\models\db\Motion;
use Yii;
use Codeception\Specify;

class ConsultationFindMotionTest extends DBTestBase
{
    use Specify;

    /**
     *
     */
    public function testFindAmendment()
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        /** @var Motion $motion */
        $motion = Motion::findOne(2);

        /** @var Amendment $amendA1 */
        $amendA1 = Amendment::findOne(1);
        /** @var Amendment $amendA2 */
        $amendA2 = Amendment::findOne(3);

        /** @var Amendment $amendA1OtherMotion */
        $amendA1OtherMotion = Amendment::findOne(2);

        $this->assertEquals($amendA1, $motion->findAmendmentWithPrefix('Ä1'));
        $this->assertEquals($amendA1, $motion->findAmendmentWithPrefix('ä1'));
        $this->assertEquals(null, $motion->findAmendmentWithPrefix('Ä1', $amendA1));
        $this->assertEquals($amendA1, $motion->findAmendmentWithPrefix('Ä1', $amendA2));





        $consultation->amendmentNumbering = GlobalCompact::getID();
        $consultation->save();
        $motion->refresh();

        $this->assertEquals($amendA1, $motion->findAmendmentWithPrefix('Ä1'));
        $this->assertEquals($amendA1, $motion->findAmendmentWithPrefix('ä1'));
        $this->assertEquals($amendA1OtherMotion, $motion->findAmendmentWithPrefix('Ä1', $amendA1));
        $this->assertEquals($amendA1, $motion->findAmendmentWithPrefix('Ä1', $amendA2));



        $amendA1->titlePrefix = '6.1';
        $amendA1->save();

        $consultation->refresh();

        $this->assertEquals(null, $motion->findAmendmentWithPrefix('6.10'));

    }
    /**
     *
     */
    public function testFindMotion()
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        /** @var Motion $motionA2 */
        $motionA2 = Motion::findOne(2);
        /** @var Motion $motionA3 */
        $motionA3 = Motion::findOne(3);

        $this->assertEquals($motionA2, $consultation->findMotionWithPrefix('A2'));
        $this->assertEquals($motionA2, $consultation->findMotionWithPrefix('a2'));
        $this->assertEquals(null, $consultation->findMotionWithPrefix('A2', $motionA2));
        $this->assertEquals($motionA2, $consultation->findMotionWithPrefix('A2', $motionA3));

        $motionA2->titlePrefix = '6.1';
        $motionA2->save();

        $consultation->refresh();

        $this->assertEquals(null, $consultation->findMotionWithPrefix('6.10'));
    }


}