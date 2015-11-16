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
        $content->template = $amendment->getMyMotion()->motionType->texTemplate->texContent;
        $content->title    = $amendment->getMyMotion()->title;
        if (!$amendment->getMyConsultation()->getSettings()->hideTitlePrefix && $amendment->titlePrefix != '') {
            $content->titlePrefix = $amendment->titlePrefix;
        }
        $content->titleLong = $amendment->titlePrefix . ' - ';
        $content->titleLong .= str_replace(
            '%PREFIX%',
            $amendment->getMyMotion()->titlePrefix,
            \Yii::t('amend', 'amendment_for_prefix')
        );

        $intro                    = explode("\n", $amendment->getMyConsultation()->getSettings()->pdfIntroduction);
        $content->introductionBig = $intro[0];
        if (count($intro) > 1) {
            array_shift($intro);
            $content->introductionSmall = implode("\n", $intro);
        }

        $initiators = [];
        foreach ($amendment->getInitiators() as $init) {
            $initiators[] = $init->getNameWithResolutionDate(false);
        }
        $initiatorsStr   = implode(', ', $initiators);
        $content->author = $initiatorsStr;

        foreach ($amendment->getDataTable() as $key => $val) {
            $content->motionDataTable .= Exporter::encodePlainString($key) . ':   &   ';
            $content->motionDataTable .= Exporter::encodePlainString($val) . '   \\\\';
        }

        if ($amendment->changeEditorial != '') {
            $title = Exporter::encodePlainString(\Yii::t('amend', 'editorial_hint'));
            $content->textMain .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $content->textMain .= TextSimple::getMotionLinesToTeX([$amendment->changeEditorial]) . "\n";
        }

        foreach ($amendment->getSortedSections(false) as $section) {
            $section->getSectionType()->printAmendmentTeX(false, $content);
        }

        if ($amendment->changeExplanation != '') {
            $title = Exporter::encodePlainString(\Yii::t('amend', 'reason'));
            $content->textMain .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $content->textMain .= TextSimple::getMotionLinesToTeX([$amendment->changeExplanation]) . "\n";
        }

        $supporters = $amendment->getSupporters();
        if (count($supporters) > 0) {
            $title = Exporter::encodePlainString(\Yii::t('amend', 'supporters'));
            $content->textMain .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $supps = [];
            foreach ($supporters as $supp) {
                $supps[] = $supp->getNameWithOrga();
            }
            $suppStr = '<p>' . Html::encode(implode('; ', $supps)) . '</p>';
            $content->textMain .= Exporter::encodeHTMLString($suppStr);
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
            $pdfLayout->printSectionHeading(\Yii::t('amend', 'editorial_hint'));
            $pdf->writeHTMLCell(170, '', 27, '', $amendment->changeEditorial, 0, 1, 0, true, '', true);
            $pdf->Ln(7);
        }

        foreach ($amendment->getSortedSections(false) as $section) {
            $section->getSectionType()->printAmendmentToPDF($pdfLayout, $pdf);
        }

        if ($amendment->changeExplanation != '') {
            $pdfLayout->printSectionHeading(\Yii::t('amend', 'reason'));
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
