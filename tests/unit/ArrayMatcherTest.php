<?php

namespace unit;

use app\components\diff\ArrayMatcher;
use Yii;
use Codeception\Specify;

class ArrayMatcherTest extends TestBase
{
    use Specify;

    /**
     */
    public function testInsertAtBeginning()
    {
        $this->markTestIncomplete('kommt noch');

        $orig    = [
            '<p>Original line</p>',
            '<p>Another original line</p>'
        ];
        $new     = [
            '<p>Inserted</p>',
            '<p>Original line</p>',
            '<p>Changed line</p>'
        ];
        $matcher = new ArrayMatcher();
        $matcher->addIgnoredString('###LINEBNUMBER###');
        list($ref, $matching) = $matcher->matchForDiff($orig, $new);
        $this->assertEquals([
            '<p>Inserted</p>',
            '<p>Original line</p>',
            '<p>Changed line</p>',
        ], $matching);
        $this->assertEquals([
            '###EMPTYINSERTED###',
            '<p>Original line</p>',
            '<p>Another original line</p>',
        ], $ref);


        $orig     = [
            '<p>Test line</p>',
            '<p>Original line</p>',
            '<p>Another original line</p>'
        ];
        $new      = [
            '<p>Inserted</p>',
            '<p>Test2 line</p>',
            '<p>Original line</p>',
            '<p>Changed line</p>'
        ];
        $matcher  = new ArrayMatcher();
        $matcher->addIgnoredString('###LINEBNUMBER###');
        list($ref, $matching) = $matcher->matchForDiff($orig, $new);


        $this->assertEquals([
            '###EMPTYINSERTED###',
            '<p>Test line</p>',
            '<p>Original line</p>',
            '<p>Another original line</p>',
        ], $ref);
        $this->assertEquals([
            '<p>Inserted</p>',
            '<p>Test2 line</p>',
            '<p>Original line</p>',
            '<p>Changed line</p>',
        ], $matching);

    }
}
