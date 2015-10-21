<?php

namespace app\views\amendment;

use app\components\latex\Content;
use app\components\latex\Exporter;
use app\components\LineSplitter;
use app\models\db\Amendment;
use app\models\sectionTypes\TextSimple;
use app\views\pdfLayouts\IPDFLayout;
use TCPDF;
use yii\helpers\Html;

class LayoutHelper
{
    /**
     * @param Amendment $amendment
     * @return Content
     * @throws \app\models\exceptions\Internal
     */
    public static function renderTeX(Amendment $amendment)
    {
        $content           = new Content();
        $content->template = $amendment->motion->motionType->texTemplate->texContent;
        $content->title    = $amendment->motion->title;
        if (!$amendment->motion->consultation->getSettings()->hideTitlePrefix && $amendment->titlePrefix != '') {
            $content->titlePrefix = $amendment->titlePrefix;
        } else {
            $content->titlePrefix = '';
        }
        $content->titleLong = $amendment->titlePrefix . ' - ';
        $content->titleLong .= str_replace('%PREFIX%', $amendment->motion->titlePrefix, 'Änderungsantrag zu %PREFIX%');

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

        if ($amendment->changeEditorial != '') {
            $title = Exporter::encodePlainString(\Yii::t('amemd', 'editorial_hint'));
            $content->text .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $lines = LineSplitter::motionPara2lines($amendment->changeEditorial, false, PHP_INT_MAX);
            $content->text .= TextSimple::getMotionLinesToTeX($lines) . "\n";
        }

        foreach ($amendment->getSortedSections(false) as $section) {
            $content->text .= $section->getSectionType()->getAmendmentTeX();
        }

        if ($amendment->changeExplanation != '') {
            $title = Exporter::encodePlainString(\Yii::t('amemd', 'reason'));
            $content->text .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $lines = LineSplitter::motionPara2lines($amendment->changeExplanation, false, PHP_INT_MAX);
            $content->text .= TextSimple::getMotionLinesToTeX($lines) . "\n";
        }

        $supporters = $amendment->getSupporters();
        if (count($supporters) > 0) {
            $title = Exporter::encodePlainString(\Yii::t('amend', 'supporters'));
            $content->text .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $supps = [];
            foreach ($supporters as $supp) {
                $supps[] = $supp->getNameWithOrga();
            }
            $suppStr = '<p>' . Html::encode(implode('; ', $supps)) . '</p>';
            $content->text .= Exporter::encodeHTMLString($suppStr);
        }

        return $content;
    }

    /**
     * @param TCPDF $pdf
     * @param IPDFLayout $pdfLayout
     * @param Amendment $amendment
     * @throws \app\models\exceptions\Internal
     */
    public static function printToPDF(TCPDF $pdf, IPDFLayout $pdfLayout, Amendment $amendment)
    {
        $pdf->AddPage();

        $pdfLayout->printAmendmentHeader($amendment);

        if ($amendment->changeEditorial != '') {
            $pdfLayout->printSectionHeading(\Yii::t('amemd', 'editorial_hint'));
            $pdf->writeHTMLCell(170, '', 27, '', $amendment->changeEditorial, 0, 1, 0, true, '', true);
            $pdf->Ln(7);
        }

        foreach ($amendment->getSortedSections(false) as $section) {
            $section->getSectionType()->printAmendmentToPDF($pdfLayout, $pdf);
        }

        if ($amendment->changeExplanation != '') {
            $pdfLayout->printSectionHeading(\Yii::t('amemd', 'reason'));
            $pdf->writeHTMLCell(170, '', 27, '', $amendment->changeExplanation, 0, 1, 0, true, '', true);
            $pdf->Ln(7);
        }

        $supporters = $amendment->getSupporters();
        if (count($supporters) > 0) {
            $pdfLayout->printSectionHeading(\Yii::t('amend', 'supporters'));
            $supportersStr = [];
            foreach ($supporters as $supp) {
                $supportersStr[] = Html::encode($supp->getNameWithOrga());
            }
            $pdf->writeHTMLCell(170, '', 27, '', implode(', ', $supportersStr), 0, 1, 0, true, '', true);
            $pdf->Ln(7);
        }
    }
}
