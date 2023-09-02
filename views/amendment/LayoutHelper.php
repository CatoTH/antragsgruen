<?php

namespace app\views\amendment;

use app\components\HTMLTools;
use app\models\sectionTypes\ISectionType;
use app\views\pdfLayouts\{IPdfWriter, IPDFLayout};
use app\components\latex\{Content, Exporter, Layout};
use app\components\Tools;
use app\models\db\{Amendment, AmendmentSection, ISupporter, Motion, TexTemplate};
use app\models\LimitedSupporterList;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

class LayoutHelper
{
    /**
     * @return array<array{title: string, section: ISectionType}>
     */
    public static function getVisibleProposedProcedureSections(Amendment $amendment, ?string $procedureToken): array
    {
        if (!$amendment->hasVisibleAlternativeProposaltext($procedureToken)) {
            return [];
        }
        $reference = $amendment->getAlternativeProposaltextReference();
        if (!$reference) {
            return [];
        }

        /** @var Amendment $referenceAmendment */
        $referenceAmendment = $reference['amendment'];
        /** @var Amendment $reference */
        $reference = $reference['modification'];

        $out = [];
        /** @var AmendmentSection[] $sections */
        $sections = $reference->getSortedSections(false);
        foreach ($sections as $section) {
            if ($referenceAmendment->id === $amendment->id) {
                $prefix = \Yii::t('amend', 'pprocedure_title_own');
            } else {
                $prefix = \Yii::t('amend', 'pprocedure_title_other') . ' ' . $referenceAmendment->getFormattedTitlePrefix();
            }
            if (!$amendment->isProposalPublic()) {
                $prefix = '[ADMIN] ' . $prefix;
            }
            $sectionType = $section->getSectionType();
            $sectionType->setMotionContext($amendment->getMyMotion());
            $out[] = [
                'title' => $prefix,
                'section' => $sectionType,
            ];
        }

        return $out;
    }

    public static function renderTeX(Amendment $amendment, TexTemplate $texTemplate): Content
    {
        $content             = new Content();
        $content->template   = $texTemplate->texContent;
        $content->titleRaw   = $amendment->getMyMotion()->title;
        $content->title      = $amendment->getMyMotion()->getTitleWithIntro();
        $content->lineLength = $amendment->getMyConsultation()->getSettings()->lineLength;
        $content->logoData   = $amendment->getMyConsultation()->getPdfLogoData();
        if (!$amendment->getMyConsultation()->getSettings()->hideTitlePrefix && $amendment->getFormattedTitlePrefix() !== '') {
            $content->titlePrefix = $amendment->getFormattedTitlePrefix();
        }
        $content->titleLong       = $amendment->getFormattedTitlePrefix() . ' - ';
        $content->titleLong       .= str_replace(
            '%PREFIX%',
            $amendment->getMyMotion()->getFormattedTitlePrefix(),
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

        if ($amendment->getMyMotionType()->getSettingsObj()->showProposalsInExports) {
            $ppSections = self::getVisibleProposedProcedureSections($amendment, null);
            foreach ($ppSections as $ppSection) {
                $ppSection['section']->setTitlePrefix($ppSection['title']);
                $ppSection['section']->printAmendmentTeX(false, $content);
            }
        }

        foreach ($amendment->getSortedSections(false) as $section) {
            $sectionType = $section->getSectionType();
            if ($amendment->getExtraDataKey(Amendment::EXTRA_DATA_VIEW_MODE_FULL)) {
                $sectionType->setDefaultToOnlyDiff(false);
            }
            $sectionType->printAmendmentTeX(false, $content);
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
        $pdfLayout->printAmendmentHeader($amendment);

        if ($amendment->changeEditorial !== '') {
            $pdfLayout->printSectionHeading(\Yii::t('amend', 'editorial_hint'));
            $pdf->writeHTMLCell(170, 0, 27, null, $amendment->changeEditorial, 0, 1, false, true, '', true);
            $pdf->Ln(7);
        }

        foreach ($amendment->getSortedSections(false) as $section) {
            $sectionType = $section->getSectionType();
            if ($amendment->getExtraDataKey(Amendment::EXTRA_DATA_VIEW_MODE_FULL)) {
                $sectionType->setDefaultToOnlyDiff(false);
            }
            $sectionType->printAmendmentToPDF($pdfLayout, $pdf);
        }

        if ($amendment->changeExplanation !== '') {
            $pdfLayout->printSectionHeading(\Yii::t('amend', 'reason'));
            $pdf->Ln(0);
            $pdf->writeHTMLCell(0, 0, null, null, $amendment->changeExplanation, 0, 1, false, true, '', true);
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
            $pdf->writeHTMLCell(170, 0, 27, null, $listStr, 0, 1, false, true, '', true);
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

        $exporter = new Exporter($layout, AntragsgruenApp::getInstance());
        $content  = static::renderTeX($amendment, $texTemplate);
        $pdf      = $exporter->createPDF([$content]);
        \Yii::$app->cache->set($amendment->getPdfCacheKey(), $pdf);

        return $pdf;
    }

    public static function printAmendmentToOdt(Amendment $amendment, \CatoTH\HTML2OpenDocument\Text $doc): void
    {
        $initiators = [];
        foreach ($amendment->amendmentSupporters as $supp) {
            if ($supp->role === ISupporter::ROLE_INITIATOR) {
                $initiators[] = $supp->getNameWithOrga();
            }
        }
        if (count($initiators) === 1) {
            $initiatorStr = \Yii::t('export', 'InitiatorSingle');
        } else {
            $initiatorStr = \Yii::t('export', 'InitiatorMulti');
        }
        $initiatorStr .= ': ' . implode(', ', $initiators);
        $doc->addReplace('/\{\{ANTRAGSGRUEN:TITLE\}\}/siu', $amendment->getTitle());
        $doc->addReplace('/\{\{ANTRAGSGRUEN:INITIATORS\}\}/siu', $initiatorStr);
        if ($amendment->getMyMotionType()->getSettingsObj()->showProposalsInExports && $amendment->proposalStatus !== null && $amendment->isProposalPublic()) {
            $doc->addReplace('/\{\{ANTRAGSGRUEN:STATUS\}\}/siu', \Yii::t('export', 'proposed_procedure') . ': ' . strip_tags($amendment->getFormattedProposalStatus(false)));
        } else {
            $doc->addReplace('/\{\{ANTRAGSGRUEN:STATUS\}\}/siu', '');
        }

        if ($amendment->changeEditorial !== '') {
            $doc->addHtmlTextBlock('<h2>' . Html::encode(\Yii::t('amend', 'editorial_hint')) . '</h2>', false);
            $editorial = HTMLTools::correctHtmlErrors($amendment->changeEditorial);
            $doc->addHtmlTextBlock($editorial, false);
        }

        if ($amendment->getMyMotionType()->getSettingsObj()->showProposalsInExports) {
            $ppSections = self::getVisibleProposedProcedureSections($amendment, null);
            foreach ($ppSections as $ppSection) {
                $ppSection['section']->setTitlePrefix($ppSection['title']);
                $ppSection['section']->printAmendmentToODT($doc);
            }
        }

        foreach ($amendment->getSortedSections(false) as $section) {
            $section->getSectionType()->printAmendmentToODT($doc);
        }

        if ($amendment->changeExplanation !== '') {
            $doc->addHtmlTextBlock('<h2>' . Html::encode(\Yii::t('amend', 'reason')) . '</h2>', false);
            $explanation = HTMLTools::correctHtmlErrors($amendment->changeExplanation);
            $doc->addHtmlTextBlock($explanation, false);
        }

        $limitedSupporters = LimitedSupporterList::createFromIMotion($amendment);
        if (count($limitedSupporters->supporters) > 0) {
            $doc->addHtmlTextBlock('<h2>' . Html::encode(\Yii::t('motion', 'supporters_heading')) . '</h2>', false);

            $supps = [];
            foreach ($limitedSupporters->supporters as $supp) {
                $supps[] = $supp->getNameWithOrga();
            }

            $doc->addHtmlTextBlock('<p>' . Html::encode(implode('; ', $supps)) . $limitedSupporters->truncatedToString(';') . '</p>', false);
        }
    }
}
