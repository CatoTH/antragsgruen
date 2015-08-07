<?php

namespace unit;

use app\components\diff\AmendmentDiffMerger;
use app\components\HTMLTools;
use app\models\db\AmendmentSection;
use Codeception\Specify;

class AmendmentDiffMergerTest extends TestBase
{
    /**
     */
    public function testMerge1()
    {
        $origText   = '<ul>
<li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li>
	<li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li>
</ul>';
        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $affectedParagraphs = [
            0 => "<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li></ul><ul><li>Neuer Punkt</li></ul>",
        ];

        $merger = new AmendmentDiffMerger();
        $merger->initByMotionParagraphs($paragraphs);
        $merger->addAmendmentParagraphs(1, $affectedParagraphs);

        $groupedParaData = $merger->getGroupedParagraphData();

        $this->assertEquals([
            [
                [
                    'amendment' => 0,
                    'text'      => '<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li></ul>',
                ],
                [
                    'amendment' => 1,
                    'text'      => '<ul><li><ins>Neuer Punkt</ins></li></ul>',
                ]
            ],
            [
                [
                    'amendment' => 0,
                    'text'      => '<ul><li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li></ul>'
                ]
            ]
        ], $groupedParaData);
    }

    /**
     */
    public function testMerge2()
    {
        $origText   = '<p>Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi.Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog</p>';
        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $affectedParagraphs = [
            0 => "<p>Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi. Woibbadinga damischa owe gwihss Sauwedda Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog</p>",
        ];

        $merger = new AmendmentDiffMerger();
        $merger->initByMotionParagraphs($paragraphs);
        $merger->addAmendmentParagraphs(2, $affectedParagraphs);

        $groupedParaData = $merger->getGroupedParagraphData();

        $this->assertEquals([
            [
                [
                    'amendment' => 0,
                    'text'      => '<p>Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix ',
                ],
                [
                    'amendment' => 2,
                    'text'      => '<del>Reiwadatschi.Weibaleid </del><ins>Reiwadatschi. Woibbadinga damischa owe gwihss Sauwedda Weibaleid </ins>',
                ],
                [
                    'amendment' => 0,
                    'text'      => 'ognudelt Ledahosn noch da Giasinga Heiwog</p>',
                ]
            ]
        ], $groupedParaData);
    }
}
