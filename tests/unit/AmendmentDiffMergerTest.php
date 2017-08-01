<?php

namespace unit;

use app\components\diff\AmendmentDiffMerger;
use app\components\HTMLTools;
use Codeception\Specify;

class AmendmentDiffMergerTest extends TestBase
{
    public function testInsertWithinDeletion()
    {
        $origText = '<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>';
        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new AmendmentDiffMerger();
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [0 => '<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore fnord et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>']);
        $merger->addAmendingParagraphs(2, [0 => '<p></p>']);

        $merger->mergeParagraphs();

        $this->assertEquals([
            ['amendment' => 0, 'text' => '<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore '],
            ['amendment' => 1, 'text' => '###INS_START###fnord ###INS_END###'],
            ['amendment' => 0, 'text' => 'et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>'],
        ], $merger->getGroupedParagraphData(0));

        $collissions = $merger->getCollidingParagraphGroups(0);
        $this->assertTrue(isset($collissions[2]));
    }

    public function testBasic()
    {
        $orig   = [
            '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch. Griasd eich midnand etza nix Gwiass woass ma ned owe.</p>'
        ];
        $new    = [
            '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch. etza nix Gwiass woass ma ned owe.</p>'
        ];
        $merger = new AmendmentDiffMerger();
        $merger->initByMotionParagraphs($orig);
        $merger->addAmendingParagraphs(1, $new);
        $merger->mergeParagraphs();
        $groupedParaData = $merger->getGroupedParagraphData(0);
        $this->assertEquals([
            ['amendment' => 0, 'text' => '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch. '],
            ['amendment' => 1, 'text' => '###DEL_START###Griasd eich midnand ###DEL_END###'],
            ['amendment' => 0, 'text' => 'etza nix Gwiass woass ma ned owe.</p>'],
        ], $groupedParaData);
    }

    public function testInsertedLinebreak()
    {
        $orig   = [
            '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch. Griasd eich midnand etza nix Gwiass woass ma ned owe.</p>'
        ];
        $new    = [
            '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch.</p>',
            '<p>Griasd eich midnand etza nix Gwiass woass ma ned owe.</p>',
        ];
        $merger = new AmendmentDiffMerger();
        $merger->initByMotionParagraphs($orig);
        $merger->addAmendingParagraphs(1, $new);
        $merger->mergeParagraphs();
        $groupedParaData = $merger->getGroupedParagraphData(0);
        $this->assertEquals([
            ['amendment' => 0, 'text' => '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch.'],
            ['amendment' => 1, 'text' => '###DEL_START### Griasd eich midnand etza nix Gwiass woass ma ned owe.###DEL_END###'],
            ['amendment' => 0, 'text' => '</p>'],
            ['amendment' => 1, 'text' => '###INS_START###<p>Griasd eich midnand etza nix Gwiass woass ma ned owe.</p>###INS_END###'],

        ], $groupedParaData);
    }

    /**
     */
    public function testInsertedParagraph()
    {
        $merger = new AmendmentDiffMerger();
        $merger->initByMotionParagraphs(['<p>Daher ist es nicht nur durch die bekannt gewordenen Vorfälle von sexueller Gewalt in der Kinder- und Jugendarbeit die Aufgabe des DBJR und aller Mitgliedsverbände, Präventionsarbeit zu diesem Thema zu leisten. Vielmehr liefert diese Arbeit auch einen Beitrag zu einer weniger gewaltvollen Gesellschaft.</p>']);
        $merger->addAmendingParagraphs(1, [0 => '<p>Der Kampf für Gleichberechtigung von Frauen und Männern stellt die Grundlage der präventiven Arbeit dar. Eine präventive Arbeit gegen sexualisierte Gewalt bedeutet eben auch sexistische Strukturen in der Gesellschaft aufzudecken und stetig dagegen anzugehen.</p>
<p>Prävention sexualisierter Gewalt ist schon lange ein wichtiges Anliegen der Jugendverbände. Mit unseren Maßnahmen zur Prävention und Intervention gegen sexualisierte Gewalt leisten wir dabei einen wichtigen Beitrag.</p>
<p>zu einer weniger gewaltvollen Gesellschaft.</p>']);
        $merger->mergeParagraphs();

        $this->assertEquals([
            ['amendment' => 0, 'text' => ''],
            ['amendment' => 1, 'text' => '###DEL_START###<p>Daher ist es nicht nur durch die bekannt gewordenen Vorfälle von sexueller Gewalt in der Kinder- und Jugendarbeit die Aufgabe des DBJR und aller Mitgliedsverbände, Präventionsarbeit zu diesem Thema zu leisten. Vielmehr liefert diese Arbeit auch einen Beitrag zu einer weniger gewaltvollen Gesellschaft.</p>###DEL_END######INS_START###<p>Der Kampf für Gleichberechtigung von Frauen und Männern stellt die Grundlage der präventiven Arbeit dar. Eine präventive Arbeit gegen sexualisierte Gewalt bedeutet eben auch sexistische Strukturen in der Gesellschaft aufzudecken und stetig dagegen anzugehen.</p>' . "\n" . '<p>Prävention sexualisierter Gewalt ist schon lange ein wichtiges Anliegen der Jugendverbände. Mit unseren Maßnahmen zur Prävention und Intervention gegen sexualisierte Gewalt leisten wir dabei einen wichtigen Beitrag.</p>' . "\n" . '<p>zu einer weniger gewaltvollen Gesellschaft.</p>###INS_END###'],
        ],
            $merger->getGroupedParagraphData(0)
        );
    }

    /**
     */
    public function testPrependPToChangedList()
    {
        $this->markTestIncomplete('kommt noch');

        $merger = new AmendmentDiffMerger();
        $merger->initByMotionParagraphs(['<ul><li>Wir Jugendverbände sehen uns in der Verantwortung, das Gedenken an den Holocaust</li></ul>']);
        $merger->addAmendingParagraphs(1, [0 => '<p>Die Zusammensetzung der in Deutschland lebenden Bevölkerung ändert.</p><ul><li>Wir stellen uns immer wieder neu der Frage.</li></ul>']);
        $merger->mergeParagraphs();
        $this->assertEquals([
            ['amendment' => 0, 'text' => ''],
            [
                'amendment' => 1,
                'text'      => '<ul><li><del>Wir Jugendverbände sehen uns in der Verantwortung, das Gedenken an den Holocaust</del></li></ul><p><ins>Die Zusammensetzung der in Deutschland lebenden Bevölkerung ändert.</ins></p><ul><li><ins>Wir stellen uns immer wieder neu der Frage.</ins></li></ul>',
            ]
        ], $merger->getGroupedParagraphData(0));
    }

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
                'text'      => '<p><strong>Demokratie und Freiheit </strong><br>' .
                    'Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. ',
            ],
            [
                'amendment' => 1,
                'text'      => '###DEL_START###Auch werden wir demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird.###DEL_END######INS_START###Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die<br>' .
                    'gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir<br>' .
                    'sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese<br>' .
                    'Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen<br>' .
                    'werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung<br>' .
                    'der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die<br>' .
                    'Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen.###INS_END###',
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
                'text'      => '<p>',
            ],
            [
                'amendment' => 2,
                'text'      => '###DEL_START###Woibbadinga damischa ###DEL_END###',
            ],
            [
                'amendment' => 0,
                'text'      => 'owe gwihss Sauwedda ded ',
            ],
            [
                'amendment' => 2,
                'text'      => '###INS_START###Hier was Neues ###INS_END###',
            ],
            [
                'amendment' => 0,
                'text'      => 'Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi ',
            ],
            [
                'amendment' => 3,
                'text'      => 'mim###DEL_START### Radl foahn Landla Leonhardifahrt, Radler###DEL_END###',
            ],
            [
                'amendment' => 0,
                'text'      => '. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ',
            ],
            [
                'amendment' => 1,
                'text'      => '###INS_START###Inserted ###INS_END###',
            ],
            [
                'amendment' => 0,
                'text'      => 'ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, ',
            ],
            [
                'amendment' => 2,
                'text'      => '###INS_START###und hier was Neues ###INS_END###',
            ],
            [
                'amendment' => 0,
                'text'      => 'gsuffa Oachkatzlschwoaf hod Wiesn.</p>',
            ],
        ], $merger->getGroupedParagraphData(0));

        $colliding = $merger->getCollidingParagraphGroups(0);
        $this->assertFalse(isset($colliding[1]));
        $this->assertTrue(isset($colliding[2]));
        $this->assertFalse(isset($colliding[3]));
        $this->assertEquals('###INS_START###schena ###INS_END###', $colliding[2][1]['text']);
    }

    /**
     * Hint, Does not collide anymore, since 3.7
     */
    public function testMergeWithComplication2()
    {
        $origText = '<p>test1 test3 test5 test7 test9 test11 test13 test15 test17 test19 test21 test23 test25</p>';

        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new AmendmentDiffMerger();
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [0 => '<p>test1 test3 test5 test6 test7 test9 test11 test13 test15 test16.1 test17 test19 test21 test22 test23 test25</p>']);
        $merger->addAmendingParagraphs(2, [0 => '<p>test1 test3 test5 test7 test9 test10 test11 test13 test15 test16.2 test17 test19 test21 test23 test25 test26</p>']);
        $merger->mergeParagraphs();

        $this->assertEquals([
            [
                'amendment' => 0,
                'text'      => '<p>test1 test3 test5 ',
            ],
            [
                'amendment' => 1,
                'text'      => '###INS_START###test6 ###INS_END###',
            ],
            [
                'amendment' => 0,
                'text'      => 'test7 test9 ',
            ],
            [
                'amendment' => 2,
                'text'      => '###INS_START###test10 ###INS_END###',
            ],
            [
                'amendment' => 0,
                'text'      => 'test11 test13 test15 ',
            ],
            [
                'amendment' => 1,
                'text'      => '###INS_START###test16.1 ###INS_END###',
            ],
            [
                'amendment' => 2,
                'text'      => '###INS_START###test16.2 ###INS_END###',
            ],
            [
                'amendment' => 0,
                'text'      => 'test17 test19 test21 ',
            ],
            [
                'amendment' => 1,
                'text'      => '###INS_START###test22 ###INS_END###',
            ],
            [
                'amendment' => 0,
                'text'      => 'test23 test25',
            ],
            [
                'amendment' => 2,
                'text'      => '###INS_START### test26###INS_END###',
            ],
            [
                'amendment' => 0,
                'text'      => '</p>',
            ],
        ], $merger->getGroupedParagraphData(0));

        $colliding = $merger->getCollidingParagraphGroups(0);
        $this->assertEquals(0, count($colliding));
    }

    /**
     * Is not colliding anymore
     */
    public function testMergeWithComplicationStripUnchangedLi()
    {
        $origText = '<ul><li>Hblas Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad.</li></ul>';

        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new AmendmentDiffMerger();
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [0 => '<ul><li>Hblas Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad.</li><li>Addition 1</li></ul>']);
        $merger->addAmendingParagraphs(2, [0 => '<ul><li>Hblas Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad.</li><li>Addition 2</li></ul>']);

        $merger->mergeParagraphs();

        $this->assertEquals([
            [
                'amendment' => 0,
                'text'      => '<ul><li>Hblas Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad.</li>',
            ],
            [
                'amendment' => 2,
                'text'      => '###INS_START###<li>Addition 2</li>###INS_END###',
            ],
            [
                'amendment' => 1,
                'text'      => '###INS_START###<li>Addition 1</li>###INS_END###',
            ],
            [
                'amendment' => 0,
                'text'      => '</ul>',
            ],
        ], $merger->getGroupedParagraphData(0));

        $colliding = $merger->getCollidingParagraphGroups(0);
        $this->assertEquals(0, count($colliding));
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
            1 => "<ul><li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li></ul>",
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
                'text'      => '###INS_START###<ul><li>Neuer Punkt</li></ul>###INS_END###',
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
                'text'      => '<p>Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi.',
            ],
            [
                'amendment' => 2,
                'text'      => '###INS_START### Woibbadinga damischa owe gwihss Sauwedda ###INS_END###',
            ],
            [
                'amendment' => 0,
                'text'      => 'Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog</p>',
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
            1 => "<p>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd!</p>",
        ];

        $merger = new AmendmentDiffMerger();
        $merger->initByMotionParagraphs($paragraphs);
        $merger->addAmendingParagraphs(3, $affectedParagraphs);
        $merger->mergeParagraphs();

        $this->assertEquals([
            [
                'amendment' => 0,
                'text'      => '<p>',
            ],
            [
                'amendment' => 3,
                'text'      => '###INS_START###New line at beginning</p><p>###INS_END###',
            ],
            [
                'amendment' => 0,
                'text'      => 'Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.',
            ],
            [
                'amendment' => 3,
                'text'      => '###INS_START###</p><p>Neuer Absatz###INS_END###',
            ],
            [
                'amendment' => 0,
                'text'      => '</p>',
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
