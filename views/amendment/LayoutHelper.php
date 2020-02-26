<?php

namespace app\views\amendment;

use app\views\pdfLayouts\{IPdfWriter, IPDFLayout};
use app\components\latex\{Content, Exporter, Layout};
use app\components\Tools;
use app\models\db\{Amendment, TexTemplate};
use app\models\LimitedSupporterList;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

class LayoutHelper
{
    public static function renderTeX(Amendment $amendment, TexTemplate $texTemplate): Content
    {
        $content             = new Content();
        $content->template   = $texTemplate->texContent;
        $content->titleRaw   = $amendment->getMyMotion()->title;
        $content->title      = $amendment->getMyMotion()->getTitleWithIntro();
        $content->lineLength = $amendment->getMyConsultation()->getSettings()->lineLength;
        $content->logoData   = $amendment->getMyConsultation()->getPdfLogoData();
        if (!$amendment->getMyConsultation()->getSettings()->hideTitlePrefix && $amendment->titlePrefix !== '') {
            $content->titlePrefix = $amendment->titlePrefix;
        }
        $content->titleLong       = $amendment->titlePrefix . ' - ';
        $content->titleLong       .= str_replace(
            '%PREFIX%',
            $amendment->getMyMotion()->titlePrefix,
            \Yii::t('amend', 'amendment_for_prefix')
        );
        $content->publicationDate = Tools::formatMysqlDate($amendment->datePublication);
        $content->typeName        = \Yii::t('export', 'amendment');

        $intro                    = explode("\n", $amendment->getMyMotionType()->getSettingsObj()->pdfIntroduction);
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

        if ($amendment->changeEditorial !== '') {
            $title             = Exporter::encodePlainString(\Yii::t('amend', 'editorial_hint'));
            $content->textMain .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $content->textMain .= Exporter::getMotionLinesToTeX([$amendment->changeEditorial]) . "\n";
        }

        foreach ($amendment->getSortedSections(false) as $section) {
            $section->getSectionType()->printAmendmentTeX(false, $content);
        }

        if ($amendment->changeExplanation !== '') {
            $title             = Exporter::encodePlainString(\Yii::t('amend', 'reason'));
            $content->textMain .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $content->textMain .= Exporter::getMotionLinesToTeX([$amendment->changeExplanation]) . "\n";
        }

        $limitedSupporters = LimitedSupporterList::createFromIMotion($amendment);
        if (count($limitedSupporters->supporters) > 0) {
            $title             = Exporter::encodePlainString(\Yii::t('amend', 'supporters'));
            $content->textMain .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $supps             = [];
            foreach ($limitedSupporters->supporters as $supp) {
                $supps[] = $supp->getNameWithOrga();
            }
            $suppStr           = '<p>' . Html::encode(implode('; ', $supps)) . $limitedSupporters->truncatedToString(';') . '</p>';
            $content->textMain .= Exporter::encodeHTMLString($suppStr);
        }

        return $content;
    }

    public static function printToPDF(IPdfWriter $pdf, IPDFLayout $pdfLayout, Amendment $amendment): void
    {
        error_reporting(error_reporting() & ~E_DEPRECATED); // TCPDF ./. PHP 7.2

        $pdfLayout->printAmendmentHeader($amendment);

        if ($amendment->changeEditorial !== '') {
            $pdfLayout->printSectionHeading(\Yii::t('amend', 'editorial_hint'));
            $pdf->writeHTMLCell(170, '', 27, '', $amendment->changeEditorial, 0, 1, 0, true, '', true);
            $pdf->Ln(7);
        }

        foreach ($amendment->getSortedSections(false) as $section) {
            $section->getSectionType()->printAmendmentToPDF($pdfLayout, $pdf);
        }

        if ($amendment->changeExplanation !== '') {
            $pdfLayout->printSectionHeading(\Yii::t('amend', 'reason'));
            $pdf->Ln(0);
            $pdf->writeHTMLCell(0, '', '', '', $amendment->changeExplanation, 0, 1, 0, true, '', true);
            $pdf->Ln(7);
        }

        $limitedSupporters = LimitedSupporterList::createFromIMotion($amendment);
        if (count($limitedSupporters->supporters) > 0) {
            $pdfLayout->printSectionHeading(\Yii::t('amend', 'supporters'));
            $supportersStr = [];
            foreach ($limitedSupporters->supporters as $supp) {
                $supportersStr[] = Html::encode($supp->getNameWithOrga());
            }
            $listStr = implode(', ', $supportersStr) . $limitedSupporters->truncatedToString(',');
            $pdf->writeHTMLCell(170, '', 27, '', $listStr, 0, 1, 0, true, '', true);
            $pdf->Ln(7);
        }
    }

    public static function createPdfTcpdf(Amendment $amendment): string
    {
        $pdfLayout = $amendment->getMyMotion()->motionType->getPDFLayoutClass();
        $pdf       = $pdfLayout->createPDFClass();

        $initiators = [];
        foreach ($amendment->getInitiators() as $init) {
            $initiators[] = $init->getNameWithResolutionDate(false);
        }

        // set document information
        $pdf->SetCreator(\Yii::t('export', 'default_creator'));
        $pdf->SetAuthor(implode(', ', $initiators));
        $pdf->SetTitle(\Yii::t('amend', 'amendment') . ' ' . $amendment->getTitle());
        $pdf->SetSubject(\Yii::t('amend', 'amendment') . ' ' . $amendment->getTitle());

        static::printToPDF($pdf, $pdfLayout, $amendment);

        return $pdf->Output('', 'S');
    }

    public static function createPdfLatex(Amendment $amendment): string
    {
        $cache = \Yii::$app->cache->get($amendment->getPdfCacheKey());
        if ($cache) {
            return $cache;
        }
        $texTemplate = $amendment->getMyMotion()->motionType->texTemplate;

        $layout             = new Layout();
        $layout->pluginRoot = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
        $layout->assetRoot  = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
        $layout->template   = $texTemplate->texLayout;
        $layout->author     = $amendment->getInitiatorsStr();
        $layout->title      = $amendment->getTitle();

        /** @var AntragsgruenApp $params */
        $params   = \yii::$app->params;
        $exporter = new Exporter($layout, $params);
        $content  = static::renderTeX($amendment, $texTemplate);
        $pdf      = $exporter->createPDF([$content]);
        \Yii::$app->cache->set($amendment->getPdfCacheKey(), $pdf);

        return $pdf;
    }
}
