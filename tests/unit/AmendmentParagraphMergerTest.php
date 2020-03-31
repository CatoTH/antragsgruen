<?php

namespace unit;

use app\components\diff\amendmentMerger\ParagraphMerger;

class AmendmentParagraphMergerTest extends TestBase
{

    public function testDeletedHash()
    {
        $words = [
            [
                'orig'         => 'werden',
                'modification' => null,
                'modifiedBy'   => null,
            ],
            [
                'orig'         => '.',
                'modification' => null,
                'modifiedBy'   => null,
            ],
            [
                'orig'         => '##', // This is a typo in the original motion that is supposed to be deleted in the amendment
                'modification' => '###DEL_START####',
                'modifiedBy'   => 27300,
            ],
            [
                'orig'         => '</em>',
                'modification' => '</em>###DEL_END###',
                'modifiedBy'   => 27300,
            ],
            [
                'orig'         => '</p>',
                'modification' => null,
                'modifiedBy'   => null,
            ],
        ];

        $grouped = ParagraphMerger::groupParagraphData($words);
        $this->assertEquals([
            [
                'amendment' => 0,
                'text'      => 'werden.',
            ],
            [
                'amendment' => 27300,
                'text'      => '###DEL_START####</em>###DEL_END###',
            ],
            [
                'amendment' => 0,
                'text'      => '</p>',
            ]
        ], $grouped);
    }
}
