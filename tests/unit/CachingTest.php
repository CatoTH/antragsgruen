<?php

namespace unit;

use app\models\db\Motion;
use app\models\db\MotionSection;
use Codeception\Specify;

class CachingTest extends DBTestBase
{
    /**
     * @param int $motionId
     * @return Motion
     */
    private function getMotion($motionId)
    {
        return Motion::findOne($motionId);
    }

    /**
     */
    public function testGlobalLineNumbering()
    {
        // Create the cache
        $motion3 = $this->getMotion(3);
        /** @var MotionSection $section */
        $section = $motion3->getSortedSections(true)[1];
        $this->assertEquals('', $section->cache);
        $section->getTextParagraphs();
        $this->assertNotEquals('', $section->cache);

        // Flush the cache of another motion
        $motion58 = $this->getMotion(58);
        $motion58->flushCacheStart();


        // The cache of the first motion still should be there
        $motion3 = $this->getMotion(3);
        /** @var MotionSection $section */
        $section = $motion3->getSortedSections(true)[1];
        $this->assertNotEquals('', $section->cache);


        // Enable global line numbering
        $settings = $motion3->consultation->getSettings();
        $settings->lineNumberingGlobal = true;
        $motion3->consultation->setSettings($settings);
        $motion3->consultation->save();


        // Flush the cache of another motion again
        $motion58 = $this->getMotion(58);
        $motion58->flushCacheStart();


        // The cache of the first motion still should be flushed now
        $motion3 = $this->getMotion(3);
        /** @var MotionSection $section */
        $section = $motion3->getSortedSections(true)[1];
        $this->assertEquals('', $section->cache);
    }
}
