<?php

namespace Tests\Unit;

use app\models\amendmentNumbering\GlobalCompact;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\db\repostory\ConsultationRepository;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class ConsultationFindMotionTest extends DBTestBase
{
    public function testFindAmendment(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        /** @var Motion $motion */
        $motion = Motion::findOne(2);

        /** @var Amendment $amendA1 */
        $amendA1 = Amendment::findOne(1);
        /** @var Amendment $amendA2 */
        $amendA2 = Amendment::findOne(3);

        /** @var Amendment $amendA2OtherMotion */
        $amendA2OtherMotion = Amendment::findOne(280);

        $this->assertEquals($amendA1, $motion->findAmendmentWithPrefix('Ä1'));
        $this->assertEquals($amendA1, $motion->findAmendmentWithPrefix('ä1'));
        $this->assertNull($motion->findAmendmentWithPrefix('Ä1', $amendA1));
        $this->assertEquals($amendA1, $motion->findAmendmentWithPrefix('Ä1', $amendA2));



        $consultation->amendmentNumbering = GlobalCompact::getID();
        $consultation->save();
        $motion->refresh();

        ConsultationRepository::flushCache();
        $this->assertEquals($amendA2, $motion->findAmendmentWithPrefix('Ä2'));
        $this->assertEquals($amendA2, $motion->findAmendmentWithPrefix('ä2'));
        $this->assertEquals($amendA2OtherMotion, $motion->findAmendmentWithPrefix('Ä2', $amendA2));
        $this->assertEquals($amendA2, $motion->findAmendmentWithPrefix('Ä2', $amendA1));



        $amendA1->titlePrefix = '6.1';
        $amendA1->save();

        $consultation->refresh();

        $this->assertNull($motion->findAmendmentWithPrefix('6.10'));
    }

    public function testFindMotion(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        /** @var Motion $motionA2 */
        $motionA2 = Motion::findOne(2);
        /** @var Motion $motionA3 */
        $motionA3 = Motion::findOne(3);

        $this->assertEquals($motionA2, $consultation->findMotionWithPrefixAndVersion('A2', Motion::VERSION_DEFAULT));
        $this->assertNull($consultation->findMotionWithPrefixAndVersion('A2', '2'));
        $this->assertEquals($motionA2, $consultation->findMotionWithPrefixAndVersion('a2', Motion::VERSION_DEFAULT));
        $this->assertNull($consultation->findMotionWithPrefixAndVersion('A2', Motion::VERSION_DEFAULT, $motionA2));
        $this->assertEquals($motionA2, $consultation->findMotionWithPrefixAndVersion('A2', Motion::VERSION_DEFAULT, $motionA3));

        $motionA2->titlePrefix = '6.1';
        $motionA2->save();

        $consultation->refresh();

        $this->assertNull($consultation->findMotionWithPrefixAndVersion('6.10', Motion::VERSION_DEFAULT));
    }
}
