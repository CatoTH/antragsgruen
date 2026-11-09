<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\models\supportTypes\SupportCollectionProgress;
use Tests\Support\Helper\TestBase;

class SupportCollectionProgressTest extends TestBase
{
    public function testWithoutFemaleQuorum(): void
    {
        $progress = new SupportCollectionProgress(min: 10, curr: 3, minFemale: null, currFemale: 2);
        $this->assertFalse($progress->hasFemaleQuorum());
        $this->assertSame(7, $progress->missing);
        $this->assertSame(0, $progress->missingFemale);
        $this->assertSame(7, $progress->getNeeded());
        $this->assertFalse($progress->isReached());
    }

    public function testFemaleQuorumDominates(): void
    {
        // Only 2 supporters are missing, but 4 more women are needed
        $progress = new SupportCollectionProgress(min: 10, curr: 8, minFemale: 5, currFemale: 1);
        $this->assertTrue($progress->hasFemaleQuorum());
        $this->assertSame(2, $progress->missing);
        $this->assertSame(4, $progress->missingFemale);
        $this->assertSame(4, $progress->getNeeded());
        $this->assertFalse($progress->isReached());
    }

    public function testExampleFromBdk(): void
    {
        $progress = new SupportCollectionProgress(min: 91, curr: 52, minFemale: 46, currFemale: 19);
        $this->assertSame(39, $progress->missing);
        $this->assertSame(27, $progress->missingFemale);
        $this->assertSame(39, $progress->getNeeded());
    }

    public function testOnlyFemaleQuorumMissing(): void
    {
        $progress = new SupportCollectionProgress(min: 5, curr: 7, minFemale: 3, currFemale: 2);
        $this->assertSame(0, $progress->missing);
        $this->assertSame(1, $progress->missingFemale);
        $this->assertSame(1, $progress->getNeeded());
        $this->assertFalse($progress->isReached());
    }

    public function testReached(): void
    {
        $progress = new SupportCollectionProgress(min: 10, curr: 12, minFemale: 5, currFemale: 7);
        $this->assertTrue($progress->isReached());
        $this->assertSame(0, $progress->getNeeded());
    }

    public function testInitiatedByOrganization(): void
    {
        $progress = new SupportCollectionProgress(min: 10, curr: 0, minFemale: 5, currFemale: 0, initiatedByOrganization: true);
        $this->assertTrue($progress->isReached());
        $this->assertSame(0, $progress->getNeeded());
    }

    public function testPercentage(): void
    {
        $this->assertSame(80, SupportCollectionProgress::getPercentage(8, 10));
        $this->assertSame(100, SupportCollectionProgress::getPercentage(12, 10));
        $this->assertSame(0, SupportCollectionProgress::getPercentage(0, 10));
        $this->assertSame(100, SupportCollectionProgress::getPercentage(0, 0));
    }

    public function testNeededTextKey(): void
    {
        $this->assertSame('collecting_needed', (new SupportCollectionProgress(min: 10, curr: 3, minFemale: null, currFemale: 0))->getNeededTextKey());
        $this->assertSame('collecting_needed_1', (new SupportCollectionProgress(min: 10, curr: 9, minFemale: null, currFemale: 0))->getNeededTextKey());
        $this->assertSame('collecting_needed_1', (new SupportCollectionProgress(min: 10, curr: 9, minFemale: 5, currFemale: 5))->getNeededTextKey());
        $this->assertSame('collecting_needed_female', (new SupportCollectionProgress(min: 10, curr: 3, minFemale: 5, currFemale: 2))->getNeededTextKey());
        $this->assertSame('collecting_needed_female_1', (new SupportCollectionProgress(min: 10, curr: 3, minFemale: 5, currFemale: 4))->getNeededTextKey());
        $this->assertSame('collecting_needed_1_female', (new SupportCollectionProgress(min: 10, curr: 9, minFemale: 5, currFemale: 4))->getNeededTextKey());
        $this->assertSame('collecting_needed_1_female', (new SupportCollectionProgress(min: 10, curr: 12, minFemale: 5, currFemale: 4))->getNeededTextKey());
        $this->assertSame('collecting_reached', (new SupportCollectionProgress(min: 10, curr: 12, minFemale: 5, currFemale: 5))->getNeededTextKey());
    }

    public function testSorting(): void
    {
        $reached = new SupportCollectionProgress(min: 10, curr: 12, minFemale: null, currFemale: 0);
        $needs7 = new SupportCollectionProgress(min: 10, curr: 3, minFemale: null, currFemale: 0);
        $needs4 = new SupportCollectionProgress(min: 10, curr: 8, minFemale: 5, currFemale: 1);
        $needs10 = new SupportCollectionProgress(min: 10, curr: 0, minFemale: null, currFemale: 0);

        $list = [$reached, $needs7, $needs10, $needs4];
        usort($list, [SupportCollectionProgress::class, 'compare']);
        $this->assertSame([$needs4, $needs7, $needs10, $reached], $list);
    }
}
