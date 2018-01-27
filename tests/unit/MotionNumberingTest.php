<?php

namespace unit;

use app\models\db\IMotion;

class MotionNumberingTest extends TestBase
{
    /**
     */
    public function testCreateNewPrefix1()
    {
        $out = IMotion::getNewTitlePrefixInternal('A1');
        $this->assertEquals('A1NEU', $out);
    }

    /**
     */
    public function testCreateNewPrefix2()
    {
        $out = IMotion::getNewTitlePrefixInternal('A1NEU1');
        $this->assertEquals('A1NEU2', $out);
    }

    /**
     */
    public function testCreateNewPrefix3()
    {
        $out = IMotion::getNewTitlePrefixInternal('A1 Neu 2');
        $this->assertEquals('A1 Neu 3', $out);
    }

    /**
     */
    public function testCreateNewPrefix4()
    {
        $out = IMotion::getNewTitlePrefixInternal('A1 Neu2 Neu3');
        $this->assertEquals('A1 Neu2 Neu4', $out);
    }

    /**
     */
    public function testCreateNewPrefix5()
    {
        $out = IMotion::getNewTitlePrefixInternal('A1 Neu2 /\ Neu3');
        $this->assertEquals('A1 Neu2 /\ Neu4', $out);
    }
}
