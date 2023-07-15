<?php

namespace Tests\Unit;

use app\components\MotionNumbering;
use Tests\Support\Helper\TestBase;

class MotionNumberingTest extends TestBase
{
    public function testCreateNewPrefix1(): void
    {
        $out = MotionNumbering::getNewTitlePrefixInternal('A1');
        $this->assertSame('A1NEU', $out);
    }

    public function testCreateNewPrefix2(): void
    {
        $out = MotionNumbering::getNewTitlePrefixInternal('A1NEU1');
        $this->assertSame('A1NEU2', $out);
    }

    public function testCreateNewPrefix3(): void
    {
        $out = MotionNumbering::getNewTitlePrefixInternal('A1 Neu 2');
        $this->assertSame('A1 Neu 3', $out);
    }

    public function testCreateNewPrefix4(): void
    {
        $out = MotionNumbering::getNewTitlePrefixInternal('A1 Neu2 Neu3');
        $this->assertSame('A1 Neu2 Neu4', $out);
    }

    public function testCreateNewPrefix5(): void
    {
        $out = MotionNumbering::getNewTitlePrefixInternal('A1 Neu2 /\ Neu3');
        $this->assertSame('A1 Neu2 /\ Neu4', $out);
    }

    public function testCreateNewVersion1(): void
    {
        $out = MotionNumbering::getNewVersion('1');
        $this->assertSame('2', $out);
    }

    public function testCreateNewVersion2(): void
    {
        $out = MotionNumbering::getNewVersion('Version 23');
        $this->assertSame('Version 24', $out);
    }

    public function testCreateNewVersion3(): void
    {
        $out = MotionNumbering::getNewVersion('Version');
        $this->assertSame('Version2', $out);
    }
}
