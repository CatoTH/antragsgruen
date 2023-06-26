<?php

namespace Tests\Unit;

use app\models\db\MotionSection;
use Codeception\Attribute\Group;
use Codeception\Attribute\Incomplete;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class AmendmentInlineDiffTest extends DBTestBase
{
    #[Incomplete('TODO')]
    public function testWholeChangedParagraph(): void
    {
        $expectedDiff = '<p><strong>Demokratie und Freiheit </strong><br>Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. <del> Auch werden wir demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird.</del>
<ins> Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die<br>
gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir<br>
sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese<br>
Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen<br>
werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung<br>
der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die<br>
Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen.</ins></p>
';
        /** @var MotionSection $section */
        $section    = MotionSection::findOne(['motionSlug' => '112', 'sectionId' => 21]);
        $paragraphs = $section->getTextParagraphObjects(true, true, true);

        $this->assertCount(1, $paragraphs);
        $this->assertCount(1, $paragraphs[0]->amendmentSections);
        $this->assertEquals($expectedDiff, $paragraphs[0]->amendmentSections[0]->strDiff);
    }
}
