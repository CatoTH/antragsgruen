<?php

namespace Tests\Unit;

use app\components\diff\amendmentMerger\SectionMerger;
use app\components\diff\DataTypes\GroupedParagraphData;
use app\components\HTMLTools;
use app\models\SectionedParagraph;
use Codeception\Attribute\Incomplete;
use Tests\Support\Helper\TestBase;

class AmendmentSectionMergerTest extends TestBase
{
    private function getGroupedParagraphData($amendment, $text): GroupedParagraphData
    {
        $data = new GroupedParagraphData();
        $data->text = $text;
        $data->amendment = $amendment;

        return $data;
    }

    public function testChangedList(): void
    {
        $merger = new SectionMerger();

        $paragraphsOrig = HTMLTools::sectionSimpleHTML('<ol class="lowerAlpha"><li>Holeri</li><li>dödldi</li></ol>');
        $merger->initByMotionParagraphs($paragraphsOrig);
        $paragraphsNew = HTMLTools::sectionSimpleHTML('<ol class="lowerAlpha"><li>Holeri</li><li>du</li><li>dödldi</li></ol>');
        $merger->addAmendingParagraphs(1, $paragraphsNew);

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<ol class="lowerAlpha" start="1"><li>Holeri</li></ol>'),
            $this->getGroupedParagraphData(1, '###INS_START###<ol class="lowerAlpha" start="2"><li>du</li></ol>###INS_END###'),
        ], $merger->getGroupedParagraphData(0));

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<ol class="lowerAlpha" start="2"><li>dödldi</li></ol>'),
        ], $merger->getGroupedParagraphData(1));
    }

    public function testInsertWithinDeletion(): void
    {
        $origText = '<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>';
        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new SectionMerger();
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore fnord et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>', 0, 0)]);
        $merger->addAmendingParagraphs(2, [new SectionedParagraph('<p></p>', 0, 0)]);

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore '),
            $this->getGroupedParagraphData(1, '###INS_START###fnord ###INS_END###'),
            $this->getGroupedParagraphData(0, 'et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>'),
        ], $merger->getGroupedParagraphData(0));

        $collisions = $merger->getCollidingParagraphGroups(0);
        $this->assertTrue(isset($collisions[2]));
    }

    public function testBasic(): void
    {
        $orig   = [
            new SectionedParagraph('<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch. Griasd eich midnand etza nix Gwiass woass ma ned owe.</p>', 0, 0),
        ];
        $new    = [
            new SectionedParagraph('<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch. etza nix Gwiass woass ma ned owe.</p>', 0, 0),
        ];
        $merger = new SectionMerger();
        $merger->initByMotionParagraphs($orig);
        $merger->addAmendingParagraphs(1, $new);
        $groupedParaData = $merger->getGroupedParagraphData(0);
        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch. '),
            $this->getGroupedParagraphData(1, '###DEL_START###Griasd eich midnand ###DEL_END###'),
            $this->getGroupedParagraphData(0, 'etza nix Gwiass woass ma ned owe.</p>'),
        ], $groupedParaData);
    }

    public function testInsertedLinebreak(): void
    {
        $orig   = [
            new SectionedParagraph('<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch. Griasd eich midnand etza nix Gwiass woass ma ned owe.</p>', 0, 0),
        ];
        $new    = [
            new SectionedParagraph('<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch.</p>', 0, 0),
            new SectionedParagraph('<p>Griasd eich midnand etza nix Gwiass woass ma ned owe.</p>', 1, 1),
        ];
        $merger = new SectionMerger();
        $merger->initByMotionParagraphs($orig);
        $merger->addAmendingParagraphs(1, $new);
        $groupedParaData = $merger->getGroupedParagraphData(0);
        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch.'),
            $this->getGroupedParagraphData(1, '###DEL_START### Griasd eich midnand etza nix Gwiass woass ma ned owe.###DEL_END###'),
            $this->getGroupedParagraphData(0, '</p>'),
            $this->getGroupedParagraphData(1, '###INS_START###<p>Griasd eich midnand etza nix Gwiass woass ma ned owe.</p>###INS_END###'),

        ], $groupedParaData);
    }

    public function testInsertedParagraph(): void
    {
        $merger = new SectionMerger();
        $merger->initByMotionParagraphs([
            new SectionedParagraph('<p>Daher ist es nicht nur durch die bekannt gewordenen Vorfälle von sexueller Gewalt in der Kinder- und Jugendarbeit die Aufgabe des DBJR und aller Mitgliedsverbände, Präventionsarbeit zu diesem Thema zu leisten. Vielmehr liefert diese Arbeit auch einen Beitrag zu einer weniger gewaltvollen Gesellschaft.</p>', 0, 0),
        ]);
        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<p>Der Kampf für Gleichberechtigung von Frauen und Männern stellt die Grundlage der präventiven Arbeit dar. Eine präventive Arbeit gegen sexualisierte Gewalt bedeutet eben auch sexistische Strukturen in der Gesellschaft aufzudecken und stetig dagegen anzugehen.</p>
<p>Prävention sexualisierter Gewalt ist schon lange ein wichtiges Anliegen der Jugendverbände. Mit unseren Maßnahmen zur Prävention und Intervention gegen sexualisierte Gewalt leisten wir dabei einen wichtigen Beitrag.</p>
<p>zu einer weniger gewaltvollen Gesellschaft.</p>', 0, 0)]);

        $this->assertEqualsCanonicalizing(
            [
            $this->getGroupedParagraphData(0, ''),
            $this->getGroupedParagraphData(1, '###DEL_START###<p>Daher ist es nicht nur durch die bekannt gewordenen Vorfälle von sexueller Gewalt in der Kinder- und Jugendarbeit die Aufgabe des DBJR und aller Mitgliedsverbände, Präventionsarbeit zu diesem Thema zu leisten. Vielmehr liefert diese Arbeit auch einen Beitrag zu einer weniger gewaltvollen Gesellschaft.</p>###DEL_END######INS_START###<p>Der Kampf für Gleichberechtigung von Frauen und Männern stellt die Grundlage der präventiven Arbeit dar. Eine präventive Arbeit gegen sexualisierte Gewalt bedeutet eben auch sexistische Strukturen in der Gesellschaft aufzudecken und stetig dagegen anzugehen.</p>' . "\n" . '<p>Prävention sexualisierter Gewalt ist schon lange ein wichtiges Anliegen der Jugendverbände. Mit unseren Maßnahmen zur Prävention und Intervention gegen sexualisierte Gewalt leisten wir dabei einen wichtigen Beitrag.</p>' . "\n" . '<p>zu einer weniger gewaltvollen Gesellschaft.</p>###INS_END###'),
            ],
            $merger->getGroupedParagraphData(0)
        );
    }

    #[Incomplete('TODO')]
    public function testPrependPToChangedList(): void
    {
        $merger = new SectionMerger();
        $merger->initByMotionParagraphs([new SectionedParagraph('<ul><li>Wir Jugendverbände sehen uns in der Verantwortung, das Gedenken an den Holocaust</li></ul>', 0, 0)]);
        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<p>Die Zusammensetzung der in Deutschland lebenden Bevölkerung ändert.</p><ul><li>Wir stellen uns immer wieder neu der Frage.</li></ul>', 0, 0)]);
        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, ''),
            $this->getGroupedParagraphData(1, '<ul><li><del>Wir Jugendverbände sehen uns in der Verantwortung, das Gedenken an den Holocaust</del></li></ul><p><ins>Die Zusammensetzung der in Deutschland lebenden Bevölkerung ändert.</ins></p><ul><li><ins>Wir stellen uns immer wieder neu der Frage.</ins></li></ul>'),
        ], $merger->getGroupedParagraphData(0));
    }

    public function testChangeWholeParagraph(): void
    {
        $origText   = '<p><strong>Demokratie und Freiheit </strong><br>
Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. Auch werden wir demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird.</p>';
        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new SectionMerger();
        $merger->initByMotionParagraphs($paragraphs);
        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<p><strong>Demokratie und Freiheit </strong><br>
Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die<br>
gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir<br>
sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese<br>
Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen<br>
werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung<br>
der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die<br>
Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen.</p>', 0, 0)]);

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p><strong>Demokratie und Freiheit </strong><br>' .
                    'Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. '),
            $this->getGroupedParagraphData(1, '###DEL_START###Auch werden wir demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird.###DEL_END######INS_START###Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die<br>' .
                    'gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir<br>' .
                    'sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese<br>' .
                    'Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen<br>' .
                    'werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung<br>' .
                    'der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die<br>' .
                    'Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen.###INS_END###'),
            $this->getGroupedParagraphData(0, '</p>'),
        ], $merger->getGroupedParagraphData(0));
    }

    public function testMergeWithComplication1_WithCollisionMerging(): void
    {
        $origText = '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>';

        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new SectionMerger();
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn Inserted ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>', 0, 0)]);
        $merger->addAmendingParagraphs(2, [new SectionedParagraph('<p>owe gwihss Sauwedda ded Hier was Neues Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim schena Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, und hier was Neues gsuffa Oachkatzlschwoaf hod Wiesn.</p>', 0, 0)]);
        $merger->addAmendingParagraphs(3, [new SectionedParagraph('<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>', 0, 0)]);


        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>'),
            $this->getGroupedParagraphData(2, '###DEL_START###Woibbadinga damischa ###DEL_END###'),
            $this->getGroupedParagraphData(0, 'owe gwihss Sauwedda ded '),
            $this->getGroupedParagraphData(2, '###INS_START###Hier was Neues ###INS_END###'),
            $this->getGroupedParagraphData(0, 'Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi '),
            $this->getGroupedParagraphData(3, 'mim ###INS_START0-2-COLLISION###schena ###INS_END######DEL_START### Radl foahn Landla Leonhardifahrt, Radler###DEL_END###'),
            $this->getGroupedParagraphData(0, '. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn '),
            $this->getGroupedParagraphData(1, '###INS_START###Inserted ###INS_END###'),
            $this->getGroupedParagraphData(0, 'ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, '),
            $this->getGroupedParagraphData(2, '###INS_START###und hier was Neues ###INS_END###'),
            $this->getGroupedParagraphData(0, 'gsuffa Oachkatzlschwoaf hod Wiesn.</p>'),
        ], $merger->getGroupedParagraphData(0));

        $colliding = $merger->getCollidingParagraphGroups(0);
        $this->assertFalse(isset($colliding[1]));
        $this->assertFalse(isset($colliding[2]));
        $this->assertFalse(isset($colliding[3]));
    }

    public function testMergeWithComplication1_WithoutCollisionMerging(): void
    {
        $origText = '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>';

        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new SectionMerger(false);
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn Inserted ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>', 0, 0)]);
        $merger->addAmendingParagraphs(2, [new SectionedParagraph('<p>owe gwihss Sauwedda ded Hier was Neues Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim schena Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, und hier was Neues gsuffa Oachkatzlschwoaf hod Wiesn.</p>', 0, 0)]);
        $merger->addAmendingParagraphs(3, [new SectionedParagraph('<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>', 0, 0)]);


        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>'),
            $this->getGroupedParagraphData(2, '###DEL_START###Woibbadinga damischa ###DEL_END###'),
            $this->getGroupedParagraphData(0, 'owe gwihss Sauwedda ded '),
            $this->getGroupedParagraphData(2, '###INS_START###Hier was Neues ###INS_END###'),
            $this->getGroupedParagraphData(0, 'Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi '),
            $this->getGroupedParagraphData(3, 'mim###DEL_START### Radl foahn Landla Leonhardifahrt, Radler###DEL_END###'),
            $this->getGroupedParagraphData(0, '. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn '),
            $this->getGroupedParagraphData(1, '###INS_START###Inserted ###INS_END###'),
            $this->getGroupedParagraphData(0, 'ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, '),
            $this->getGroupedParagraphData(2, '###INS_START###und hier was Neues ###INS_END###'),
            $this->getGroupedParagraphData(0, 'gsuffa Oachkatzlschwoaf hod Wiesn.</p>'),
        ], $merger->getGroupedParagraphData(0));

        $colliding = $merger->getCollidingParagraphGroups(0);
        $this->assertFalse(isset($colliding[1]));
        $this->assertTrue(isset($colliding[2]));
        $this->assertFalse(isset($colliding[3]));
        $this->assertEqualsCanonicalizing('###INS_START###schena ###INS_END###', $colliding[2][1]->text);
    }

    /**
     * Hint, Does not collide anymore, since 3.7
     */
    public function testMergeWithComplication2_WithoutCollisionMerging(): void
    {
        $origText = '<p>test1 test3 test5 test7 test9 test11 test13 test15 test17 test19 test21 test23 test25</p>';

        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new SectionMerger(false);
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<p>test1 test3 test5 test6 test7 test9 test11 test13 test15 test16.1 test17 test19 test21 test22 test23 test25</p>', 0, 0)]);
        $merger->addAmendingParagraphs(2, [new SectionedParagraph('<p>test1 test3 test5 test7 test9 test10 test11 test13 test15 test16.2 test17 test19 test21 test23 test25 test26</p>', 0, 0)]);

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>test1 test3 test5 '),
            $this->getGroupedParagraphData(1, '###INS_START###test6 ###INS_END###'),
            $this->getGroupedParagraphData(0, 'test7 test9 '),
            $this->getGroupedParagraphData(2, '###INS_START###test10 ###INS_END###'),
            $this->getGroupedParagraphData(0, 'test11 test13 test15 '),
            $this->getGroupedParagraphData(1, '###INS_START###test16.1 ###INS_END###'),
            $this->getGroupedParagraphData(2, '###INS_START###test16.2 ###INS_END###'),
            $this->getGroupedParagraphData(0, 'test17 test19 test21 '),
            $this->getGroupedParagraphData(1, '###INS_START###test22 ###INS_END###'),
            $this->getGroupedParagraphData(0, 'test23 test25'),
            $this->getGroupedParagraphData(2, '###INS_START### test26###INS_END###'),
            $this->getGroupedParagraphData(0, '</p>'),
        ], $merger->getGroupedParagraphData(0));

        $colliding = $merger->getCollidingParagraphGroups(0);
        $this->assertEqualsCanonicalizing(0, count($colliding));
    }

    /**
     * Hint, Does not collide anymore, since 3.7
     */
    public function testMergeWithComplication2_WithCollisionMerging(): void
    {
        $origText = '<p>test1 test3 test5 test7 test9 test11 test13 test15 test17 test19 test21 test23 test25</p>';

        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new SectionMerger();
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<p>test1 test3 test5 test6 test7 test9 test11 test13 test15 test16.1 test17 test19 test21 test22 test23 test25</p>', 0, 0)]);
        $merger->addAmendingParagraphs(2, [new SectionedParagraph('<p>test1 test3 test5 test7 test9 test10 test11 test13 test15 test16.2 test17 test19 test21 test23 test25 test26</p>', 0, 0)]);

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>test1 test3 test5 '),
            $this->getGroupedParagraphData(1, '###INS_START###test6 ###INS_END###'),
            $this->getGroupedParagraphData(0, 'test7 test9 '),
            $this->getGroupedParagraphData(2, '###INS_START###test10 ###INS_END###'),
            $this->getGroupedParagraphData(0, 'test11 test13 test15 '),
            $this->getGroupedParagraphData(2, '###INS_START0-1-COLLISION###test16.1 ###INS_END######INS_START###test16.2 ###INS_END###'),
            $this->getGroupedParagraphData(0, 'test17 test19 test21 '),
            $this->getGroupedParagraphData(1, '###INS_START###test22 ###INS_END###'),
            $this->getGroupedParagraphData(0, 'test23 test25'),
            $this->getGroupedParagraphData(2, '###INS_START### test26###INS_END###'),
            $this->getGroupedParagraphData(0, '</p>'),
        ], $merger->getGroupedParagraphData(0));

        $colliding = $merger->getCollidingParagraphGroups(0);
        $this->assertEqualsCanonicalizing(0, count($colliding));
    }

    /**
     * Is not colliding anymore
     */
    public function testMergeWithComplicationStripUnchangedLi(): void
    {
        $origText = '<ul><li>Hblas Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad.</li></ul>';

        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new SectionMerger();
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<ul><li>Hblas Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad.</li><li>Addition 1</li></ul>', 0, 0)]);
        $merger->addAmendingParagraphs(2, [new SectionedParagraph('<ul><li>Hblas Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad.</li><li>Addition 2</li></ul>', 0, 0)]);

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<ul><li>Hblas Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad.</li>'),
            $this->getGroupedParagraphData(2, '###INS_START###<li>Addition 2</li>###INS_END###'),
            $this->getGroupedParagraphData(1, '###INS_START###<li>Addition 1</li>###INS_END###'),
            $this->getGroupedParagraphData(0, '</ul>'),
        ], $merger->getGroupedParagraphData(0));

        $colliding = $merger->getCollidingParagraphGroups(0);
        $this->assertEqualsCanonicalizing(0, count($colliding));
    }

    public function testMerge1(): void
    {
        $origText   = '<ul>
<li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li>
	<li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li>
</ul>';
        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $affectedParagraphs = [
            new SectionedParagraph("<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li></ul><ul><li>Neuer Punkt</li></ul>", 0, 0),
            new SectionedParagraph("<ul><li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li></ul>", 1, 1),
        ];

        $merger = new SectionMerger();
        $merger->initByMotionParagraphs($paragraphs);
        $merger->addAmendingParagraphs(1, $affectedParagraphs);

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li></ul>'),
            $this->getGroupedParagraphData(1, '###INS_START###<ul><li>Neuer Punkt</li></ul>###INS_END###'),
        ], $merger->getGroupedParagraphData(0));
        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<ul><li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li></ul>'),
        ], $merger->getGroupedParagraphData(1));
    }

    public function testMerge2(): void
    {
        $origText   = '<p>Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi.Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog</p>';
        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $affectedParagraphs = [
            new SectionedParagraph("<p>Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi. Woibbadinga damischa owe gwihss Sauwedda Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog</p>", 0, 0),
        ];

        $merger = new SectionMerger();
        $merger->initByMotionParagraphs($paragraphs);
        $merger->addAmendingParagraphs(2, $affectedParagraphs);

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi.'),
            $this->getGroupedParagraphData(2, '###INS_START### Woibbadinga damischa owe gwihss Sauwedda ###INS_END###'),
            $this->getGroupedParagraphData(0, 'Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog</p>'),
        ], $merger->getGroupedParagraphData(0));
    }

    public function testMerge3(): void
    {
        $origText = '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>
<p>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd!</p>';

        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $affectedParagraphs = [
            new SectionedParagraph("<p>New line at beginning</p><p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p><p>Neuer Absatz</p>", 0, 0),
            new SectionedParagraph("<p>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd!</p>", 1, 1),
        ];

        $merger = new SectionMerger();
        $merger->initByMotionParagraphs($paragraphs);
        $merger->addAmendingParagraphs(3, $affectedParagraphs);

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>'),
            $this->getGroupedParagraphData(3, '###INS_START###New line at beginning</p><p>###INS_END###'),
            $this->getGroupedParagraphData(0, 'Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.'),
            $this->getGroupedParagraphData(3, '###INS_START###</p><p>Neuer Absatz###INS_END###'),
            $this->getGroupedParagraphData(0, '</p>'),
        ], $merger->getGroupedParagraphData(0));

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd!</p>'),
        ], $merger->getGroupedParagraphData(1));
    }

    public function testCollisionWithMultipleMergableParts(): void
    {
        $origText = '<p>A beginning. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>';
        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new SectionMerger();
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<p>A beginning. Bavaria ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>', 0, 0)]);
        $merger->addAmendingParagraphs(2, [new SectionedParagraph('<p>A beginning. Zombie ipsum dolor sit amet o’ha wea nia ausgähd, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>', 0, 0)]);

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>A beginning. '),
            // Both replacements of "Lorem" should be included, one as a collision
            $this->getGroupedParagraphData(1, '###DEL_START###Lorem###DEL_END######INS_START###Bavaria###INS_END### ###DEL_START0-2-COLLISION###Lorem###DEL_END######INS_START0-2-COLLISION###Zombie###INS_END### '),
            $this->getGroupedParagraphData(0, 'ipsum dolor sit '),
            // A regular replacement of the second amendment (that was in collision in the previous diff)
            $this->getGroupedParagraphData(2, 'amet###DEL_START###, consetetur sadipscing elitr###DEL_END######INS_START### o’ha wea nia ausgähd###INS_END###, '),
            $this->getGroupedParagraphData(0, 'sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>'),
        ], $merger->getGroupedParagraphData(0));

        $collisions = $merger->getCollidingParagraphGroups(0);
        $this->assertCount(0, $collisions);
    }

    public function testCollisionWithTwoDeletedParts_WithCollisionMerging(): void
    {
        // This tests that partially overlapping deletions CAN be merged
        $origText = '<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>';
        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new SectionMerger();
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<p>Lorem ipsum dolor sit amet, tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>', 0, 0)]);
        $merger->addAmendingParagraphs(2, [new SectionedParagraph('<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, et dolore magna aliquyam erat, sed diam voluptua.</p>', 0, 0)]);

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, '),
            // Both deletions in one, after another
            $this->getGroupedParagraphData(2, '###DEL_START###sed diam nonumy eirmod tempor invidunt ut labore ###DEL_END######DEL_START0-1-COLLISION###consetetur sadipscing elitr, sed diam nonumy eirmod ###DEL_END###'),
            $this->getGroupedParagraphData(0, 'et dolore magna aliquyam erat, sed diam voluptua.</p>'),
        ], $merger->getGroupedParagraphData(0));

        $collisions = $merger->getCollidingParagraphGroups(0);
        $this->assertCount(0, $collisions);
    }

    public function testCollisionWithTwoDeletedParts_WithoutCollisionMerging(): void
    {
        $origText = '<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>';
        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new SectionMerger(false);
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<p>Lorem ipsum dolor sit amet, tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>', 0, 0)]);
        $merger->addAmendingParagraphs(2, [new SectionedParagraph('<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, et dolore magna aliquyam erat, sed diam voluptua.</p>', 0, 0)]);

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, '),
            // Both deletions in one, after another
            $this->getGroupedParagraphData(2, '###DEL_START###sed diam nonumy eirmod tempor invidunt ut labore ###DEL_END###'),
            $this->getGroupedParagraphData(0, 'et dolore magna aliquyam erat, sed diam voluptua.</p>'),
        ], $merger->getGroupedParagraphData(0));

        $collisions = $merger->getCollidingParagraphGroups(0);
        $this->assertCount(1, $collisions);
        $this->assertTrue(isset($collisions[1]));
        $this->assertSame('###DEL_START###consetetur sadipscing elitr, sed diam nonumy eirmod ###DEL_END###', $collisions[1][1]->text);
    }

    public function testCollisionTooBigToBeMerged_WithCollisionMerging(): void
    {
        // This tests that a collision can NOT be merged when it is too long
        $origText = '<p>test1 test3 test5 test7 test9 test11 test13 test15 test17 test19 test21 test23 test25 test27 test29 test31 test33 test33 test35 test37 test39</p>';

        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new SectionMerger(true);
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<p>test1 test3 test5 test7 test9 test11 test13 test15 test17 test19 test21 Replacement no. 1 test25 test27 test29 test31 test33 test33 test35 test37 test39</p>', 0, 0)]);
        $merger->addAmendingParagraphs(2, [new SectionedParagraph('<p>test1 test3 test5 test7 test9 test11 test13 test15 test17 test19 test21 ' .
                                                'Here we are inserting a rather long text. As this exceeds the limit set in ParagraphMerger::$collisionMergingLimit, this should lead to a collision' .
                                                'test23 test25 test27 test29 test31 test33 test33 test35 test37 test39</p>', 0, 0)]);


        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>test1 test3 test5 test7 test9 test11 test13 test15 test17 test19 test21 '),
            $this->getGroupedParagraphData(1, '###DEL_START###test23###DEL_END######INS_START###Replacement no. 1###INS_END### '),
            $this->getGroupedParagraphData(0, 'test25 test27 test29 test31 test33 test33 test35 test37 test39</p>'),
        ], $merger->getGroupedParagraphData(0));

        $colliding = $merger->getCollidingParagraphGroups(0);
        $this->assertEqualsCanonicalizing(1, count($colliding));
        $this->assertTrue(isset($colliding[2]));
        $this->assertSame('###DEL_START###test23###DEL_END######INS_START###Here we are inserting a rather long text. As this exceeds the limit set in ParagraphMerger::$collisionMergingLimit, this should lead to a collisiontest23###INS_END### ', $colliding[2][1]->text);
    }

    public function testCollisionTooHtmlishToBeMerged_WithCollisionMerging(): void
    {
        // This tests that a collision can NOT be merged when it contains HTML tags
        $origText = '<p>test1 test3 test5 test7 test9 test11 test13 test15 test17 test19 test21 test23 test25 test27 test29 test31 test33 test33 test35 test37 test39</p>';

        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new SectionMerger(true);
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<p>test1 test3 test5 test7 test9 test11 test13 test15 test17 test19 test21 Replacement no. 1 test25 test27 test29 test31 test33 test33 test35 test37 test39</p>', 0, 0)]);
        $merger->addAmendingParagraphs(2, [new SectionedParagraph('<p>test1 test3 test5 test7 test9 test11 test13 test15 test17 test19 test21 <strong>Replacement no. 2</strong> test25 test27 test29 test31 test33 test33 test35 test37 test39</p>', 0, 0)]);


        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>test1 test3 test5 test7 test9 test11 test13 test15 test17 test19 test21 '),
            $this->getGroupedParagraphData(1, '###DEL_START###test23###DEL_END######INS_START###Replacement no. 1###INS_END### '),
            $this->getGroupedParagraphData(0, 'test25 test27 test29 test31 test33 test33 test35 test37 test39</p>'),
        ], $merger->getGroupedParagraphData(0));

        $colliding = $merger->getCollidingParagraphGroups(0);
        $this->assertEqualsCanonicalizing(1, count($colliding));
        $this->assertTrue(isset($colliding[2]));
        $this->assertSame('###DEL_START###test23 ###DEL_END######INS_START###<strong>Replacement no. 2</strong> ###INS_END###', $colliding[2][1]->text);
    }

    public function testCollisionSeveralConfusingCollisions_WithCollisionMerging(): void
    {
        $origText = '<p>test1 test3 test5 test7 test9 test11 test13 test15 test17 test19 test21 test23 test25 test27 test29 test31 test33 test33 test35 test37 test39</p>';

        $paragraphs = HTMLTools::sectionSimpleHTML($origText);

        $merger = new SectionMerger(true);
        $merger->initByMotionParagraphs($paragraphs);

        $merger->addAmendingParagraphs(1, [new SectionedParagraph('<p>test1 test3 test5 test7 test9 test27 test29 test31 test33 test33 test35 test37 test39</p>', 0, 0)]);
        $merger->addAmendingParagraphs(2, [new SectionedParagraph('<p>test1 test3 test5 test7 test9 test11 test13 test15 test17.8 test19 test21 test23 test25 test27 test29 test31 test33 test33 test35 test37 test39</p>', 0, 0)]);
        $merger->addAmendingParagraphs(3, [new SectionedParagraph('<p>test1 test3 test5 test7 test9 test11 test13 test16.test17 Test18 test19 test21 test23 test25 test27 test33 test33 test35 test37 test39</p>', 0, 0)]);
        $merger->addAmendingParagraphs(4, [new SectionedParagraph('<p>test1 test3 test5 test7 test9 test11 test13 test15 test18 test19 test21 test23 test25 test27 test29 test31 test33 test33 test35 test37 test39</p>', 0, 0)]);

        $this->assertEqualsCanonicalizing([
            $this->getGroupedParagraphData(0, '<p>test1 test3 test5 test7 test9 test11 test13 test15 '),
            $this->getGroupedParagraphData(2, 'test1###DEL_START0-3-COLLISION###5 ###DEL_END######INS_START0-3-COLLISION###6.###INS_END###test17 ###INS_START0-3-COLLISION###Test18 ###INS_END###7###INS_START###.8###INS_END### ###DEL_START1-1-COLLISION###test11 test13 test15 test17 test19 test21 test23 test25 ###DEL_END###test1###DEL_START2-4-COLLISION###7###DEL_END######INS_START2-4-COLLISION###8###INS_END### '),
            $this->getGroupedParagraphData(0, 'test19 test21 test23 test25 test27 '),
            $this->getGroupedParagraphData(3, '###DEL_START###test29 test31 ###DEL_END###'),
            $this->getGroupedParagraphData(0, 'test33 test33 test35 test37 test39</p>'),
        ], $merger->getGroupedParagraphData(0));

        $colliding = $merger->getCollidingParagraphGroups(0);
        $this->assertEqualsCanonicalizing(0, count($colliding));
    }
}
