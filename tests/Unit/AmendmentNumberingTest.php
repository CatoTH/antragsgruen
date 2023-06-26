<?php

namespace Tests\Unit;

use app\models\amendmentNumbering\ByLine;
use app\models\amendmentNumbering\GlobalCompact;
use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\amendmentNumbering\PerMotionCompact;
use app\models\amendmentNumbering\PerMotionEnglish;
use app\models\db\Amendment;
use app\models\db\Motion;
use Codeception\Attribute\Group;
use Tests\Support\AcceptanceTester;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class AmendmentNumberingTest extends DBTestBase
{
    public function testMaxTitlePrefixNumber(): void
    {
        $prefixes = [
            'A1-Ä3neu2',
            'A1-Ä4',
            'A1-Ä13neu',
        ];
        $expect   = 13;

        $out = IAmendmentNumbering::getMaxTitlePrefixNumber($prefixes);
        $this->assertEquals($expect, $out);
    }

    public function testGlobalNumbering(): void
    {
        $amend = new Amendment();

        /** @var Motion $motion */
        $motion = Motion::findOne(2);

        $sorter = new GlobalCompact();
        $expect = AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX;
        $out    = $sorter->getAmendmentNumber($amend, $motion);

        $this->assertEquals($expect, $out);


        /** @var Motion $motion */
        $motion = Motion::findOne(3);

        $sorter = new GlobalCompact();
        $expect = AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX;
        $out    = $sorter->getAmendmentNumber($amend, $motion);

        $this->assertEquals($expect, $out);


        /** @var Motion $motion */
        $motion = Motion::findOne(58);

        $sorter = new GlobalCompact();
        $expect = AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX;
        $out    = $sorter->getAmendmentNumber($amend, $motion);

        $this->assertEquals($expect, $out);
    }

    public function testPerMotionNumbering(): void
    {
        $amend = new Amendment();

        /** @var Motion $motion */
        $motion = Motion::findOne(2);

        $sorter = new PerMotionCompact();
        $expect = AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX;
        $out    = $sorter->getAmendmentNumber($amend, $motion);

        $this->assertEquals($expect, $out);


        /** @var Motion $motion */
        $motion = Motion::findOne(3);

        $sorter = new PerMotionCompact();
        $expect = 'Ä2';
        $out    = $sorter->getAmendmentNumber($amend, $motion);

        $this->assertEquals($expect, $out);


        /** @var Motion $motion */
        $motion = Motion::findOne(58);

        $sorter = new PerMotionCompact();
        $expect = 'Ä1';
        $out    = $sorter->getAmendmentNumber($amend, $motion);

        $this->assertEquals($expect, $out);
    }

    public function testByLineNumbering(): void
    {
        $sorter = new ByLine();

        /** @var Amendment $amendment */
        $amendment              = Amendment::findOne(3);
        $amendment->titlePrefix = '';

        $out    = $sorter->getAmendmentNumber($amendment, $amendment->getMyMotion());
        $expect = 'A2-009';

        $this->assertEquals($expect, $out);


        $amendment              = Amendment::findOne(272);
        $amendment->titlePrefix = '';

        $out    = $sorter->getAmendmentNumber($amendment, $amendment->getMyMotion());
        $expect = 'A2-027';

        $this->assertEquals($expect, $out);


        $amendment              = Amendment::findOne(274);
        $amendment->titlePrefix = '';
        foreach ($amendment->getMyMotion()->amendments as $amend) {
            if ($amend->id === 272) {
                $amend->titlePrefix = 'A2-027';
            }
        }

        $out    = $sorter->getAmendmentNumber($amendment, $amendment->getMyMotion());
        $expect = 'A2-027-2';

        $this->assertEquals($expect, $out);



        $amendment              = Amendment::findOne(273);
        $amendment->titlePrefix = '';
        foreach ($amendment->getMyMotion()->amendments as $amend) {
            if ($amend->id === 272) {
                $amend->titlePrefix = 'A2-027';
            }
            if ($amend->id === 274) {
                $amend->titlePrefix = 'A2-027-2';
            }
        }
        $out                    = $sorter->getAmendmentNumber($amendment, $amendment->getMyMotion());
        $expect                 = 'A2-027-3';

        $this->assertEquals($expect, $out);
    }

    public function testPerMotionNumberingEnglish1(): void
    {
        $amend = new Amendment();
        $motion = Motion::findOne(2);

        $sorter = new PerMotionEnglish();
        $out = $sorter->getAmendmentNumber($amend, $motion);

        $this->assertEquals('A2 A8', $out);
    }

    public function testPerMotionNumberingEnglish2(): void
    {
        $amend = new Amendment();
        $motion = Motion::findOne(2);

        $existingAmendment = Amendment::findOne(276);
        $existingAmendment->titlePrefix = 'M2 A9';
        $existingAmendment->save();

        $sorter = new PerMotionEnglish();
        $out    = $sorter->getAmendmentNumber($amend, $motion);

        $this->assertEquals('A2 A10', $out);
    }
}
