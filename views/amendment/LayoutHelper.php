<?php

namespace app\views\amendment;

use app\components\latex\Content;
use app\components\latex\Exporter;
use app\components\LineSplitter;
use app\models\db\Amendment;
use app\models\sectionTypes\TextSimple;

class LayoutHelper
{
    /**
     * @return Content
     */
    public static function renderTeX(Amendment $amendment)
    {
        $content              = new Content();
        $content->template    = $amendment->motion->motionType->texTemplate->texContent;
        $content->title       = $amendment->motion->title;
        $content->titlePrefix = $amendment->titlePrefix . ' zu ' . $amendment->motion->titlePrefix;
        $content->titleLong   = $amendment->getTitle();

        $intro                    = explode("\n", $amendment->motion->consultation->getSettings()->pdfIntroduction);
        $content->introductionBig = $intro[0];
        if (count($intro) > 1) {
            array_shift($intro);
            $content->introductionSmall = implode("\n", $intro);
        } else {
            $content->introductionSmall = '';
        }

        $initiators = [];
        foreach ($amendment->getInitiators() as $init) {
            $initiators[] = $init->getNameWithResolutionDate(false);
        }
        $initiatorsStr   = implode(', ', $initiators);
        $content->author = $initiatorsStr;

        $content->motionDataTable = '';
        foreach ($amendment->getDataTable() as $key => $val) {
            $content->motionDataTable .= Exporter::encodePlainString($key) . ':   &   ';
            $content->motionDataTable .= Exporter::encodePlainString($val) . '   \\\\';
        }

        $content->text = '';

        foreach ($amendment->getSortedSections(false) as $section) {
            $content->text .= $section->getSectionType()->getAmendmentTeX();
        }

        if ($amendment->changeExplanation != '') {
            $title = Exporter::encodePlainString('Begründung');
            $content->text .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $lines = LineSplitter::motionPara2lines($amendment->changeExplanation, false, PHP_INT_MAX);
            $content->text .= TextSimple::getMotionLinesToTeX($lines) . "\n";
        }

        $supporters = $amendment->getSupporters();
        if (count($supporters) > 0) {

        }

        return $content;
    }
}
