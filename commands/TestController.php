<?php
namespace app\commands;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\Diff;
use app\components\HTMLTools;
use app\components\LineSplitter;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\sectionTypes\ISectionType;
use yii\console\Controller;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

/**
 * Commands not needed for production environments but for testing miscellaneous stuff
 * @package app\commands
 */
class TestController extends Controller
{
    /**
     * @throws \yii\db\Exception
     */
    public function actionDiff()
    {

        $orig = '<p>Um die ökonomischen, sozialen und ökologischen Probleme in Angriff zu nehmen, müssen wir umsteuern. Dazu brauchen wir einen Green New Deal für Europa, der eine umfassende Antwort auf die Krisen der Gegenwart gibt. Er enthält mehrere Komponenten: eine nachhaltige Investitionsstrategie, die auf ökologische Innovationen setzt statt auf maßlose Deregulierung; eine Politik der sozialen Gerechtigkeit statt der Gleichgültigkeit gegenüber der ständig schärferen Spaltung unserer Gesellschaften; eine Politik, die auch unpopuläre Strukturreformen angeht, wenn diese zu nachhaltigem Wachstum und mehr Gerechtigkeit beitragen; ein Politik die Probleme wie Korruption und mangelnde Rechtsstaatlichkeit angehen und eine Politik, die die Glaubwürdigkeit in Europa, dass Schulden auch bedient werden, untermauert.</p>
<p>[b]Die Kaputtsparpolitik ist gescheitert[/b]<br>
Die Strategie zur Krisenbewältigung der letzten fünf Jahre hat zwar ein wichtiges Ziel erreicht: Der Euro, als entscheidendes Element der europäischen Integration und des europäischen Zusammenhalts, konnte bislang gerettet werden. Dafür hat Europa neue Instrumente und Mechanismen geschaffen, wie den Euro-Rettungsschirm mit dem Europäischen Stabilitätsmechanismus (ESM) oder die Bankenunion. Aber diese Instrumente allein werden die tiefgreifenden Probleme nicht lösen - weder politisch noch wirtschaftlich.</p>';

        $new = '<p>Um die ökonomischen, sozialen und ökologischen Probleme in Angriff zu nehmen, müssen wir umsteuern. Dazu brauchen wir einen Green New Deal für Europa, der eine umfassende Antwort auf die Krisen der Gegenwart gibt. Er enthält mehrere Komponenten: eine nachhaltige Investitionsstrategie, die auf ökologische Innovationen setzt statt auf Deregulierung und blindes Vertrauen in die Heilkräfte des Marktes; einen Weg zu mehr sozialer Gerechtigkeit statt der Gleichgültigkeit gegenüber der ständig schärferen Spaltung unserer Gesellschaften; ein Wirtschaftsmodell, das auch unbequeme Strukturreformen mit einbezieht, wenn diese zu nachhaltigem Wachstum und mehr Gerechtigkeit beitragen; ein Politik die Probleme wie Korruption und mangelnde Rechtsstaatlichkeit angehen und eine Politik, die die Glaubwürdigkeit in Europa, dass Schulden auch bedient werden, untermauert.</p>
<p>[b]Die Kaputtsparpolitik ist gescheitert[/b]<br>
Die Strategie zur Krisenbewältigung der letzten fünf Jahre hat zwar ein wichtiges Ziel erreicht: Der Euro, als entscheidendes Element der europäischen Integration und des europäischen Zusammenhalts, konnte bislang gerettet werden. Dafür hat Europa neue Instrumente und Mechanismen geschaffen, wie den Euro-Rettungsschirm mit dem Europäischen Stabilitätsmechanismus (ESM) oder die Bankenunion. Aber diese Instrumente allein werden die tiefgreifenden Probleme nicht lösen - weder politisch noch wirtschaftlich.</p>';

        var_dump(AmendmentSectionFormatter::getDiffLinesWithNumbersDebug($orig, $new));
        return;

        echo "PART 1\n";
        $origParas = HTMLTools::sectionSimpleHTML($orig);
        $orig2 = '';
        foreach ($origParas as $para) {
            $linesOut   = LineSplitter::motionPara2lines($para, true, 80);
            $orig2 .= implode(' ', $linesOut) . "\n";
        }
        var_dump($orig2);


        /** @var Amendment $amendment */
        $amendment = Amendment::findOne(2);

        foreach ($amendment->sections as $section) {
            if ($section->consultationSetting->type == ISectionType::TYPE_TITLE) {
                continue;
            }
            if ($section->consultationSetting->type == ISectionType::TYPE_TEXT_SIMPLE) {
                $formatter  = new \app\components\diff\AmendmentSectionFormatter($section);
                $diffGroups = $formatter->getInlineDiffGroupedLines();

                if (count($diffGroups) > 0) {
                    echo '<section id="section_' . $section->sectionId . '" class="motionTextHolder">';
                    echo '<h3 class="green">' . Html::encode($section->consultationSetting->title) . '</h3>';
                    echo '<div id="section_' . $section->sectionId . '_0" class="paragraph lineNumbers">';
                    echo \app\models\sectionTypes\TextSimple::formatDiffGroup($diffGroups);
                    echo '</div>';
                    echo '</section>';
                }
            }
        }
    }
}
