<?php

namespace unit;

use app\models\amendmentNumbering\ByLine;
use app\models\amendmentNumbering\GlobalCompact;
use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\amendmentNumbering\PerMotionCompact;
use app\models\db\Amendment;
use app\models\db\Motion;
use Codeception\Specify;

class AmendmentNumberingTest extends DBTestBase
{

    /**
     */
    public function testMaxTitlePrefixNumber()
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

    /**
     */
    public function testGlobalNumbering()
    {
        $amend = new Amendment();

        /** @var Motion $motion */
        $motion = Motion::findOne(2);

        $sorter = new GlobalCompact();
        $expect = \AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX;
        $out    = $sorter->getAmendmentNumber($amend, $motion);

        $this->assertEquals($expect, $out);


        /** @var Motion $motion */
        $motion = Motion::findOne(3);

        $sorter = new GlobalCompact();
        $expect = \AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX;
        $out    = $sorter->getAmendmentNumber($amend, $motion);

        $this->assertEquals($expect, $out);


        /** @var Motion $motion */
        $motion = Motion::findOne(58);

        $sorter = new GlobalCompact();
        $expect = \AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX;
        $out    = $sorter->getAmendmentNumber($amend, $motion);

        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testPerMotionNumbering()
    {
        $amend = new Amendment();

        /** @var Motion $motion */
        $motion = Motion::findOne(2);

        $sorter = new PerMotionCompact();
        $expect = \AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX;
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

    /**
     */
    public function testByLineNumbering()
    {
        /** @var Amendment $amendment */
        $amendment              = Amendment::findOne(3);
        $amendment->titlePrefix = '';

        $sorter = new ByLine();
        $out    = $sorter->getAmendmentNumber($amendment, $amendment->motion);
        $expect = 'A2-009-1';

        $this->assertEquals($expect, $out);
    }
}
