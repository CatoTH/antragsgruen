<?php

namespace unit;

use app\models\db\Consultation;
use app\models\db\MotionSection;
use Codeception\Specify;

class CachingTest extends DBTestBase
{
    /**
     */
    public function testGlobalLineNumbering()
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        // Create the cache
        $motion3 = $consultation->getMotion(3);

        /** @var MotionSection $section */
        $section = $motion3->getSortedSections(true)[1];
        $this->assertEquals('', $section->cache);
        $section->getTextParagraphLines();
        $this->assertNotEquals('', $section->cache);

        // Flush the cache of another motion
        $motion58 = $consultation->getMotion(58);
        $motion58->flushCacheStart();


        // The cache of the first motion still should be there
        $motion3 = $consultation->getMotion(3);
        /** @var MotionSection $section */
        $section = $motion3->getSortedSections(true)[1];
        $this->assertNotEquals('', $section->cache);


        // Enable global line numbering
        $settings = $consultation->getSettings();
        $settings->lineNumberingGlobal = true;
        $consultation->setSettings($settings);
        $consultation->save();

        // Flush the cache of another motion again
        $motion58 = $consultation->getMotion(58);
        $motion58->flushCacheStart();

        // The cache of the first motion still should be flushed now
        $motion3 = $consultation->getMotion(3);
        $motion3->refresh();

        /** @var MotionSection $section */
        $section = $motion3->getSortedSections(true)[1];
        $section->refresh();
        $this->assertEquals('', $section->cache);
    }
}
