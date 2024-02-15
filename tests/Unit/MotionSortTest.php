<?php

namespace Tests\Unit;

use app\components\MotionSorter;
use Tests\Support\Helper\TestBase;

class MotionSortTest extends TestBase
{
    public function testStd(): void
    {
        $orig   = [
            'A3',
            'A2neu',
            'A4',
            'A10'
        ];
        $expect = [
            'A2neu',
            'A3',
            'A4',
            'A10'
        ];
        $out    = MotionSorter::getSortedMotionsSortTest($orig);
        $this->assertSame($expect, $out);
    }

    public function testNeuVariations1(): void
    {
        $orig   = [
            'A10 neub',
            'A3',
            'A2neu1',
            'A4neu2',
        ];
        $expect = [
            'A2neu1',
            'A3',
            'A4neu2',
            'A10 neub'
        ];
        $out    = MotionSorter::getSortedMotionsSortTest($orig);
        $this->assertSame($expect, $out);
    }

    public function testNeuVariations2(): void
    {
        $orig = ['W-01-WES', 'W-01-NEU'];
        $expect = ['W-01-NEU', 'W-01-WES'];
        $out = MotionSorter::getSortedMotionsSortTest($orig);
        $this->assertSame($expect, $out);
    }

    public function testAgenda(): void
    {
        $orig   = [
            '6.10.',
            '6.4.',
            '6.2. Neu',
            '4.3. Neu 2',
        ];
        $expect = [
            '4.3. Neu 2',
            '6.2. Neu',
            '6.4.',
            '6.10.',
        ];
        $out    = MotionSorter::getSortedMotionsSortTest($orig);
        $this->assertSame($expect, $out);
    }

    public function testStripBeginning(): void
    {
        $this->assertSame(['1', '2'], MotionSorter::stripCommonBeginning('ab1', 'ab2'));
        $this->assertSame(['d', 'c'], MotionSorter::stripCommonBeginning('abd', 'abc'));
        $this->assertSame(['1', ''], MotionSorter::stripCommonBeginning('ab1', 'ab'));

        $this->assertSame(['1', '2'], MotionSorter::stripCommonBeginning('1.1', '1.2'));
        $this->assertSame(['1', '3'], MotionSorter::stripCommonBeginning('1.2.1', '1.2.3'));
        $this->assertSame(['1neu', '1neu2'], MotionSorter::stripCommonBeginning('1.1neu', '1.1neu2'));
    }

    public function testAgenda2(): void
    {
        $orig   = [
            '6.1.',
            '6.2.',
            '6.15. Neu b',
            '6.14neu',
        ];
        $expect = [
            '6.1.',
            '6.2.',
            '6.14neu',
            '6.15. Neu b',
        ];
        $out = MotionSorter::getSortedMotionsSortTest($orig);
        $this->assertSame($expect, $out);
    }
}
