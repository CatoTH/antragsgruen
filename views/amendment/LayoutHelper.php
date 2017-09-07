<?php

namespace app\views\amendment;

use app\components\HTMLTools;
use app\components\latex\Content;
use app\components\latex\Exporter;
use app\components\latex\Layout;
use app\models\db\Amendment;
use app\models\db\ISupporter;
use app\models\db\TexTemplate;
use app\models\sectionTypes\TextSimple;
use app\models\settings\AntragsgruenApp;
use app\views\pdfLayouts\IPDFLayout;
use TCPDF;
use yii\helpers\Html;

class LayoutHelper
{
    /**
     * @param Amendment $amendment
     * @param TexTemplate $texTemplate
     * @return Content
     */
    public static function renderTeX(Amendment $amendment, TexTemplate $texTemplate)
    {
        $content           = new Content();
        $content->template = $texTemplate->texContent;
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
            $content->textMain .= Exporter::getMotionLinesToTeX([$amendment->changeEditorial]) . "\n";
        }

        foreach ($amendment->getSortedSections(false) as $section) {
            $section->getSectionType()->printAmendmentTeX(false, $content);
        }

        if ($amendment->changeExplanation != '') {
            $title = Exporter::encodePlainString(\Yii::t('amend', 'reason'));
            $content->textMain .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $content->textMain .= Exporter::getMotionLinesToTeX([$amendment->changeExplanation]) . "\n";
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
     * @param \FPDI $pdf
     * @param IPDFLayout $pdfLayout
     * @param Amendment $amendment
     * @throws \app\models\exceptions\Internal
     */
    public static function printToPDF(\FPDI $pdf, IPDFLayout $pdfLayout, Amendment $amendment)
    {
        $pdf->startPageGroup();
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

    /**
     * @param Amendment $amendment
     * @return string
     */
    public static function createPdf(Amendment $amendment)
    {
        $cache = \Yii::$app->cache->get($amendment->getPdfCacheKey());
        if ($cache) {
            return $cache;
        }
        $texTemplate = $amendment->getMyMotion()->motionType->texTemplate;

        $layout            = new Layout();
        $layout->assetRoot = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
        //$layout->templateFile = \yii::$app->basePath . DIRECTORY_SEPARATOR .
        //    'assets' . DIRECTORY_SEPARATOR . 'motion_std.tex';
        $layout->template = $texTemplate->texLayout;
        $layout->author   = $amendment->getInitiatorsStr();
        $layout->title    = $amendment->getTitle();

        /** @var AntragsgruenApp $params */
        $params   = \yii::$app->params;
        $exporter = new Exporter($layout, $params);
        $content  = \app\views\amendment\LayoutHelper::renderTeX($amendment, $texTemplate);
        $pdf      = $exporter->createPDF([$content]);
        \Yii::$app->cache->set($amendment->getPdfCacheKey(), $pdf);
        return $pdf;
    }

    /**
     * @param Amendment $amendment
     * @return string
     */
    public static function createOdt(Amendment $amendment)
    {
        /** @var \app\models\settings\AntragsgruenApp $config */
        $config = \yii::$app->params;

        $template = $amendment->getMyMotion()->motionType->getOdtTemplateFile();
        $doc      = new \CatoTH\HTML2OpenDocument\Text([
            'templateFile' => $template,
            'tmpPath'      => $config->tmpDir,
            'trustHtml'    => true,
        ]);

        $DEBUG = (isset($_REQUEST['src']) && YII_ENV == 'dev');

        if ($DEBUG) {
            echo "<pre>";
        }

        $initiators = [];
        $supporters = [];
        foreach ($amendment->amendmentSupporters as $supp) {
            if ($supp->role == ISupporter::ROLE_INITIATOR) {
                $initiators[] = $supp->getNameWithOrga();
            }
            if ($supp->role == ISupporter::ROLE_SUPPORTER) {
                $supporters[] = $supp->getNameWithOrga();
            }
        }
        if (count($initiators) == 1) {
            $initiatorStr = \Yii::t('export', 'InitiatorSingle');
        } else {
            $initiatorStr = \Yii::t('export', 'InitiatorMulti');
        }
        $initiatorStr .= ': ' . implode(', ', $initiators);
        if ($amendment->getMyMotion()->agendaItem) {
            $doc->addReplace('/\{\{ANTRAGSGRUEN:ITEM\}\}/siu', $amendment->getMyMotion()->agendaItem->title);
        } else {
            $doc->addReplace('/\{\{ANTRAGSGRUEN:ITEM\}\}/siu', '');
        }
        $doc->addReplace('/\{\{ANTRAGSGRUEN:TITLE\}\}/siu', $amendment->getTitle());
        $doc->addReplace('/\{\{ANTRAGSGRUEN:INITIATORS\}\}/siu', $initiatorStr);


        if ($amendment->changeEditorial != '') {
            $doc->addHtmlTextBlock('<h2>' . Html::encode(\Yii::t('amend', 'editorial_hint')) . '</h2>', false);
            $editorial = HTMLTools::correctHtmlErrors($amendment->changeEditorial);
            $doc->addHtmlTextBlock($editorial, false);
        }

        foreach ($amendment->getSortedSections(false) as $section) {
            $section->getSectionType()->printAmendmentToODT($doc);
        }

        if ($amendment->changeExplanation != '') {
            $doc->addHtmlTextBlock('<h2>' . Html::encode(\Yii::t('amend', 'reason')) . '</h2>', false);
            $explanation = HTMLTools::correctHtmlErrors($amendment->changeExplanation);
            $doc->addHtmlTextBlock($explanation, false);
        }

        return $doc->finishAndGetDocument();
    }
}
