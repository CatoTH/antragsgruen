<?php

namespace Tests\Unit;

use app\models\db\Motion;
use app\models\db\MotionSection;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class MotionShowInlineAmendmentTest extends DBTestBase
{
    /**
     */
    public function testDbjr1(): void
    {
        /** @var Motion $motion */
        $motion = Motion::findOne(113);
        /** @var MotionSection $section */
        $section = $motion->getSortedSections()[1];
        $paragraphs     = $section->getTextParagraphObjects(false, true, true);
        $paragraph = $paragraphs[8];
        foreach ($paragraph->amendmentSections as $amendmentSection) {
            $expect = '<p>3. Senkung von Umwelt-, Verbraucher*innenschutz-, Arbeits-, Sozial- und Datenschutzstandards: Da durch die alleinige Senkung/Abschaffung von Zöllen kaum mehr Gewinnsteigerungen möglich sind, wird es in weiten Teilen der Abkommen um die Angleichung von Standards gehen. Dabei steht zu befürchten, dass eine Anpassung „nach unten“ vorgenommen wird. Es droht insbesondere in den Bereichen Chemiepolitik, Landwirtschaft, Energiepolitik, öffentliche Beschaffung und Daseinsvorsorge, Finanzdienstleistungen, Arbeitnehmer*innenrechte und Datenschutz eine Verschlechterung für <del>Verbraucher*innen</del><ins>Mensch</ins>, Natur und Umwelt.</p>';
            $this->assertEquals($expect, str_replace('###LINENUMBER###', '', $amendmentSection->strDiff));
        }
        $paragraph = $paragraphs[9];
        foreach ($paragraph->amendmentSections as $amendmentSection) {
            $expect = '<p>4. Deregulierung öffentlicher Dienstleistungen und Kulturgüter: Die vorgesehene Öffnung der Märkte im Dienstleistungssektor droht, öffentliche Beschaffung, Gesundheitswesen, Wasserversorgung und Bildung noch stärker zu liberalisieren. Damit würden schlechte Beispiele Schule machen: die öffentliche Daseinsvorsorge wird <ins>noch weiter </ins>privatisiert, verbunden mit Kostensteigerungen für Verbraucher*innen und Gewinnmaximierung von Großkonzernen.</p>';
            $this->assertEquals($expect, str_replace('###LINENUMBER###', '', $amendmentSection->strDiff));
        }
    }
}
