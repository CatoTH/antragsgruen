<?php

namespace unit;

use app\components\diff\AmendmentDiffMerger;
use app\components\HTMLTools;
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
        $merger->addAmendingParagraphs(1, $affectedParagraphs);
        $merger->mergeParagraphs();

        $this->assertEquals([
            [
                'amendment' => 0,
                'text'      => '<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li></ul>',
            ],
            [
                'amendment' => 1,
                'text'      => '<ul><li><ins>Neuer Punkt</ins></li></ul>',
            ]
        ], $merger->getGroupedParagraphData(0));
        $this->assertEquals([
            [
                'amendment' => 0,
                'text'      => '<ul><li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li></ul>'
            ]
        ], $merger->getGroupedParagraphData(1));
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
        $merger->addAmendingParagraphs(2, $affectedParagraphs);
        $merger->mergeParagraphs();

        $this->assertEquals([
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
        ], $merger->getGroupedParagraphData(0));
    }

    /**
     */
    public function testMerge3()
    {
        $origText = '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>
<p>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd!</p>';

        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $affectedParagraphs = [
            0 => "<p>New line at beginning</p><p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p><p>Neuer Absatz</p>",
        ];

        $merger = new AmendmentDiffMerger();
        $merger->initByMotionParagraphs($paragraphs);
        $merger->addAmendingParagraphs(3, $affectedParagraphs);
        $merger->mergeParagraphs();

        $this->assertEquals([
            [
                'amendment' => 0,
                'text'      => '',
            ],
            [
                'amendment' => 3,
                'text'      => '<p><ins>New line at beginning</ins></p>',
            ],
            [
                'amendment' => 0,
                'text'      => '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>',
            ],
            [
                'amendment' => 3,
                'text'      => '<p><ins>Neuer Absatz</ins></p>',
            ],
            [
                'amendment' => 0,
                'text'      => '',
            ]
        ], $merger->getGroupedParagraphData(0));

        $this->assertEquals([
            [
                'amendment' => 0,
                'text'      => '<p>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd!</p>'
            ]
        ], $merger->getGroupedParagraphData(1));
    }


    /**
     */
    public function testMergeWithComplication1()
    {
        $origText = '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>';

        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new AmendmentDiffMerger();
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [0 => '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn Inserted ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>']);
        $merger->addAmendingParagraphs(2, [0 => '<p>Woibbadinga damischa owe gwihss Sauwedda ded Hier was Neues Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim schena Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, und hier was Neues gsuffa Oachkatzlschwoaf hod Wiesn.</p>']);
        $merger->addAmendingParagraphs(3, [0 => '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>']);

        $merger->mergeParagraphs();

        $this->assertEquals([
            [
                'amendment' => 0,
                'text'      => '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi ',
            ],
            [
                'amendment' => 3,
                'text'      => '<del>mim Radl foahn Landla Leonhardifahrt, Radler. </del><ins>mim. </ins>',
            ],
            [
                'amendment' => 0,
                'text'      => 'Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ',
            ],
            [
                'amendment' => 1,
                'text'      => '<ins>Inserted </ins>',
            ],
            [
                'amendment' => 0,
                'text'      => 'ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>',
            ]
        ], $merger->getGroupedParagraphData(0));

        $colliding = $merger->getGroupedCollidingSections(0);
        $this->assertEquals([2], array_keys($colliding));
        $collidingStr = AmendmentDiffMerger::formatGroupedCollidingSection($colliding[2]);
        $this->assertEquals('<p>Woibbadinga damischa owe gwihss Sauwedda ded <ins>Hier was Neues </ins>Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim <ins>schena </ins>Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, <ins>und hier was Neues </ins>gsuffa Oachkatzlschwoaf hod Wiesn.</p>', $collidingStr);
    }
}
