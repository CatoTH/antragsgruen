<?php

namespace Tests\Unit;

use app\components\diff\amendmentMerger\ParagraphMerger;
use app\components\diff\DataTypes\GroupedParagraphData;
use app\components\diff\DataTypes\ParagraphMergerWord;
use Tests\Support\Helper\TestBase;

class AmendmentParagraphMergerTest extends TestBase
{
    private function getParagraphMergerWord($orig, $modification, $modifiedBy): ParagraphMergerWord
    {
        $data = new ParagraphMergerWord();
        $data->orig = $orig;
        $data->modification = $modification;
        $data->modifiedBy = $modifiedBy;

        return $data;
    }

    public function testDeletedHash(): void
    {
        $words = [
            $this->getParagraphMergerWord('werden', null, null),
            $this->getParagraphMergerWord('.', null, null),
            $this->getParagraphMergerWord('##', '###DEL_START####', 27300),  // This is a typo in the original motion that is supposed to be deleted in the amendment
            $this->getParagraphMergerWord('</em>', '</em>###DEL_END###', 27300),
            $this->getParagraphMergerWord('</p>', null, null),
        ];

        $grouped = ParagraphMerger::groupParagraphData($words);

        $expected1 = new GroupedParagraphData();
        $expected1->text = 'werden.';
        $expected1->amendment = 0;

        $expected2 = new GroupedParagraphData();
        $expected2->text = '###DEL_START####</em>###DEL_END###';
        $expected2->amendment = 27300;

        $expected3 = new GroupedParagraphData();
        $expected3->text = '</p>';
        $expected3->amendment = 0;

        $this->assertEqualsCanonicalizing([$expected1, $expected2, $expected3], $grouped);
    }
}
