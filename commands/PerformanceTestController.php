<?php

namespace app\commands;

use app\components\diff\Diff;
use app\components\diff\DiffRenderer;
use app\components\HTMLTools;
use app\models\SectionedParagraph;
use yii\console\Controller;

class PerformanceTestController extends Controller
{
    private function getmicrotime(): int
    {
        $x = explode(" ", microtime());

        return intval($x[1]) * 1000 + intval($x[0]) * 1000;
    }

    public function actionDiff(): void
    {
        $str1 = (string)file_get_contents(__DIR__ . '/../assets/diff-motion.txt');
        $str2 = (string)file_get_contents(__DIR__ . '/../assets/diff-amend.txt');
        $origParagraphs = HTMLTools::sectionSimpleHTML($str1);
        $newParagraphs = HTMLTools::sectionSimpleHTML($str2);

        $t1 = $this->getmicrotime();

        for ($i = 0; $i < 10; $i++) {
            $diff = new Diff();
            $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES); // hint: disable cache!
        }

        $t2 = $this->getmicrotime();

        echo ($t2 - $t1) . "ms\n";
        echo intval(memory_get_peak_usage(true) / 1024) . "byte\n";
    }
}
