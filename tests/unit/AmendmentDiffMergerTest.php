<?php

namespace unit;

use app\components\diff\AmendmentDiffMerger;
use app\components\HTMLTools;
use Codeception\Specify;

class AmendmentDiffMergerTest extends TestBase
{
    /**
     */
    public function testChangeWholeParagraph()
    {
        $origText   = '<p><strong>Demokratie und Freiheit </strong><br>
Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. Auch werden wir demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird.</p>';
        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new AmendmentDiffMerger();
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [0 => '<p><strong>Demokratie und Freiheit </strong><br>
Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die<br>
gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir<br>
sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese<br>
Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen<br>
werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung<br>
der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die<br>
Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen.</p>']);

        $merger->mergeParagraphs();

        $this->assertEquals([
            [
                'amendment' => 0,
                'text'      => '<p><strong>Demokratie und Freiheit </strong><br>Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. ',
            ],
            [
                'amendment' => 1,
                'text' => '<del>Auch werden wir demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird.</del><ins>Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die<br>gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir<br>sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese<br>Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen<br>werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung<br>der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die<br>Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen.</ins>',
            ],
            [
                'amendment' => 0,
                'text'      => '</p>',
            ]
        ], $merger->getGroupedParagraphData(0));
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
        $merger->addAmendingParagraphs(2, [0 => '<p>owe gwihss Sauwedda ded Hier was Neues Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim schena Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, und hier was Neues gsuffa Oachkatzlschwoaf hod Wiesn.</p>']);
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

        $collidingStrs = $merger->getWrappedGroupedCollidingSections(0, 5);
        $this->assertEquals([2], array_keys($collidingStrs));
        $this->assertEquals('<del>Woibbadinga damischa </del>owe gwihss Sauwedda ded <ins>Hier was Neues </ins>Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim <ins>schena </ins>Radl foahn Landla Leonhardifahrt, Radler. ...<br>...is. Biaschlegl soi oans, zwoa, <ins>und hier was Neues </ins>gsuffa Oachkatzlschwoaf hod Wiesn.', $collidingStrs[2]);

        $collidingStrs = $merger->getWrappedGroupedCollidingSections(0, 4);
        $this->assertEquals([2], array_keys($collidingStrs));
        $this->assertEquals('<del>Woibbadinga damischa </del>owe gwihss Sauwedda ded <ins>Hier was Neues </ins>Charivari dei heid gfoids ...<br>...Maßkruag wo hi mim <ins>schena </ins>Radl foahn Landla Leonhardifahrt, ...<br>...Biaschlegl soi oans, zwoa, <ins>und hier was Neues </ins>gsuffa Oachkatzlschwoaf hod Wiesn.', $collidingStrs[2]);
    }

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
}
