<?php

namespace unit;

use app\components\diff\AmendmentDiffMerger;
use app\components\diff\Diff;
use app\components\HTMLTools;
use Codeception\Specify;

class AmendmentDiffMergerTest extends TestBase
{
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
            ['amendment' => 0, 'text' => '<p>'],
            ['amendment' => 1, 'text' => '<del>Daher ist es nicht nur durch die bekannt gewordenen Vorfälle von sexueller Gewalt in der Kinder- und Jugendarbeit die Aufgabe des DBJR und aller Mitgliedsverbände, Präventionsarbeit zu diesem Thema zu leisten. Vielmehr liefert diese Arbeit auch einen Beitrag </del><ins>Der Kampf für Gleichberechtigung von Frauen und Männern stellt die Grundlage der präventiven Arbeit dar. Eine präventive Arbeit gegen sexualisierte Gewalt bedeutet eben auch sexistische Strukturen in der Gesellschaft aufzudecken und stetig dagegen anzugehen.</ins></p><p><ins>Prävention sexualisierter Gewalt ist schon lange ein wichtiges Anliegen der Jugendverbände. Mit unseren Maßnahmen zur Prävention und Intervention gegen sexualisierte Gewalt leisten wir dabei einen wichtigen Beitrag.</ins></p><p>'],
            ['amendment' => 0, 'text' => 'zu einer weniger gewaltvollen Gesellschaft.</p>'],
        ],
            $merger->getGroupedParagraphData(0)
        );
    }

    /**
     */
    public function testPrependPToChangedList()
    {
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
                'text'      => '<p><strong>Demokratie und Freiheit </strong><br>Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. ',
            ],
            [
                'amendment' => 1,
                'text'      => '<del>Auch werden wir demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird.</del><ins>Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die<br>gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir<br>sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese<br>Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen<br>werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung<br>der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die<br>Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen.</ins>',
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


    /**
     */
    public function testAmendmentAffectedParagraphs()
    {
        $orig     = [
            '<p><strong>Anspruch und Ausblick</strong></p>',
            '<p>Die Zusammensetzung der in Deutschland lebenden Bevölkerung ändert sich auch weiterhin stetig. Neue Mitglieder, neue Herkunftsstaaten machen die Gesellschaft vielfältiger und gehen mit neuen kulturellen Hintergründen, Erfahrungen und biographischen Bezügen ebenso einher, wie mit neuen historischen Bezugspunkte und einer Verschiebung ihrer Relevanz untereinander. Nicht zuletzt werden die Menschen, die aktuell nach Deutschland flüchten und zumindest eine Zeit lang hier bleiben werden, diesen Prozess verstärken.</p>',
            '<p>Die Stärkung einer europäischen Identität – ohne die Verwischung historischer Verantwortung und politischer Kontinuitäten – ist für eine zukünftige Erinnerungspolitik ein wesentlicher Aspekt, der auch Erinnerungskulturen prägen wird und in der Erinnerungsarbeit aufgegriffen werden muss.</p>',
            '<p>Gleiches gilt für die Jugendverbände und –ringe als Teil dieser Gesellschaft. Wir als Jugendverbände und –ringe im DBJR nehmen uns der sich daraus ergebenden Herausforderungen an:</p>',
            '<ul><li>Wir stellen uns immer wieder neu der Frage, wie Jugendverbände der zunehmenden kulturellen Vielfalt in ihrer verbandlichen Erinnerungskultur und ihrer Erinnerungsarbeit gerecht werden und gleichzeitig die jeweils eigene, auch kulturelle Identität, die den Verband und seine Attraktivität ausmacht, wahren können.</li></ul>',
            '<ul><li>Wir Jugendverbände sehen uns in der Verantwortung, das Gedenken an den Holocaust und die nationalsozialistischen Verbrechen, die von Deutschland ausgingen, wach zu halten und gemeinsam Sorge dafür zu tragen, „dass Auschwitz nie wieder sei!“.</li></ul>',
            '<ul><li>Wir sehen die Notwendigkeit eines stetigen Austarierens und Diskurses, um sich angemessen mit anderen historischen Ereignissen auseinanderzusetzen, die aufgrund der Herkunftsgeschichte vieler Mitglieder relevant werden, ohne dabei den Holocaust in irgendeiner Weise zu relativieren.</li></ul>',
            '<ul><li>Den o.g. Diskursen müssen sich Jugendverbände kontinuierlich stellen – jeder für sich alleine und alle gemeinsam. Als Arbeitsgemeinschaft der Jugendverbände und Landesjugendringe sieht der DBJR hier eine Aufgabe. Er wird diese Diskurse anregen und dafür eine Plattform bieten.</li></ul>',
        ];
        $amend    = [
            '<p><strong>Anspruch und Ausblick</strong></p>',
            '<p>Die Zusammensetzung der in Deutschland lebenden Bevölkerung ändert sich auch weiterhin stetig. Neue Mitglieder, neue Herkunftsstaaten machen die Gesellschaft vielfältiger und gehen mit neuen kulturellen Hintergründen, Erfahrungen und biographischen Bezügen ebenso einher, wie mit neuen historischen Bezugspunkten und einer Verschiebung ihrer Relevanz untereinander. Nicht zuletzt werden die Menschen, die aktuell nach Deutschland flüchten und zumindest eine Zeit lang hier bleiben werden, diesen Prozess verstärken.</p>',
            '<p>Wir als Jugendverbände und –ringe im DBJR nehmen uns der sich daraus ergebenden Herausforderungen an:</p>',
            '<ul><li>Wir Jugendverbände sehen uns in der Verantwortung, das Gedenken an den Holocaust und die nationalsozialistischen Verbrechen, die von Deutschland ausgingen, wach zu halten und gemeinsam Sorge dafür zu tragen, „dass Auschwitz nie wieder sei!“.</li></ul>',
            '<ul><li>Wir stellen uns immer wieder neu der Frage, wie Jugendverbände der zunehmenden kulturellen Vielfalt in ihrer verbandlichen Erinnerungskultur und ihrer Erinnerungsarbeit gerecht werden und gleichzeitig die jeweils eigene, auch kulturelle Identität, die den Verband und seine Attraktivität ausmacht, wahren können.</li></ul>',
            '<ul><li>Wir sehen die Notwendigkeit eines stetigen Austarierens und Diskurses, um sich angemessen mit anderen historischen Ereignissen auseinanderzusetzen, die aufgrund der Herkunftsgeschichte vieler Mitglieder relevant werden, ohne dabei den Holocaust in irgendeiner Weise zu relativieren.</li></ul>',
            '<ul><li>Den o.g. Diskursen müssen sich Jugendverbände kontinuierlich stellen – jeder für sich alleine und alle gemeinsam. Als Arbeitsgemeinschaft der Jugendverbände und Landesjugendringe sieht der DBJR hier eine Aufgabe. Er wird diese Diskurse anregen und dafür eine Plattform bieten.</li></ul>',
        ];
        $expected = [
            1 => '<p>Die Zusammensetzung der in Deutschland lebenden Bevölkerung ändert sich auch weiterhin stetig. Neue Mitglieder, neue Herkunftsstaaten machen die Gesellschaft vielfältiger und gehen mit neuen kulturellen Hintergründen, Erfahrungen und biographischen Bezügen ebenso einher, wie mit neuen historischen Bezugspunkten und einer Verschiebung ihrer Relevanz untereinander. Nicht zuletzt werden die Menschen, die aktuell nach Deutschland flüchten und zumindest eine Zeit lang hier bleiben werden, diesen Prozess verstärken.</p>',
            2 => '',
            3 => '<p>Wir als Jugendverbände und –ringe im DBJR nehmen uns der sich daraus ergebenden Herausforderungen an:</p>',
            4 => '',
            5 => '<ul><li>Wir Jugendverbände sehen uns in der Verantwortung, das Gedenken an den Holocaust und die nationalsozialistischen Verbrechen, die von Deutschland ausgingen, wach zu halten und gemeinsam Sorge dafür zu tragen, „dass Auschwitz nie wieder sei!“.</li></ul><ul><li>Wir stellen uns immer wieder neu der Frage, wie Jugendverbände der zunehmenden kulturellen Vielfalt in ihrer verbandlichen Erinnerungskultur und ihrer Erinnerungsarbeit gerecht werden und gleichzeitig die jeweils eigene, auch kulturelle Identität, die den Verband und seine Attraktivität ausmacht, wahren können.</li></ul>',
        ];

        $diff = new Diff();
        $out  = $diff->computeAmendmentAffectedParagraphs($orig, $amend);
        $this->assertEquals($expected, $out);
    }
}
