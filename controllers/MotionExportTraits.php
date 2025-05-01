<?php

namespace app\controllers;

use app\models\forms\AdminMotionFilterForm;
use app\views\pdfLayouts\IPDFLayout;
use app\models\settings\{PrivilegeQueryContext, Privileges, AntragsgruenApp};
use app\components\{IMotionStatusFilter, RequestContext, Tools, UrlHelper};
use app\models\exceptions\NotFound;
use app\models\http\{BinaryFileResponse,
    HtmlErrorResponse,
    HtmlResponse,
    RedirectResponse,
    ResponseInterface,
    RestApiResponse};
use app\models\mergeAmendments\Init;
use app\models\db\{Amendment, Consultation, IMotion, Motion, TexTemplate};
use app\models\exceptions\ExceptionBase;
use app\models\MotionSectionChanges;
use app\views\motion\LayoutHelper;
use yii\web\NotFoundHttpException;

/**
 * @property \Yii\base\Action $action
 * @property Consultation $consultation
 * @property \app\models\settings\Layout $layoutParams
 *
 * @method string render(string $template, array $params)
 * @method string renderPartial(string $template, array $params)
 */
trait MotionExportTraits
{
    public function actionViewimage(string $motionSlug, int $sectionId, ?string $showAlways = null): ResponseInterface
    {
        $motion    = $this->getMotionWithCheck($motionSlug);

        foreach ($motion->getActiveSections() as $section) {
            if ($section->sectionId === $sectionId) {
                if (!$motion->isReadable() && $section->getShowAlwaysToken() !== $showAlways &&
                    !$this->consultation->havePrivilege(Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::motion($motion))
                ) {
                    return new HtmlResponse($this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]));
                }
                if ($section->getSectionType()->isEmpty()) {
                    return new HtmlErrorResponse(404, 'Image not found');
                }
                $metadata                    = json_decode($section->metadata, true);
                return new BinaryFileResponse(
                    BinaryFileResponse::mimeTypeToType($metadata['mime']),
                    $section->getData(),
                    false,
                    null,
                    $this->layoutParams->isRobotsIndex($this->action)
                );
            }
        }
        return new HtmlErrorResponse(404, 'Image not found');
    }

    /**
     * @throws NotFoundHttpException
     * @throws NotFound
     */
    public function actionViewpdf(string $motionSlug, int $sectionId, ?string $showAlways = null): ResponseInterface
    {
        $motion    = $this->getMotionWithCheck($motionSlug);

        foreach ($motion->getActiveSections() as $section) {
            if ($section->sectionId === $sectionId) {
                if (!$motion->isReadable() && $section->getShowAlwaysToken() !== $showAlways &&
                    !$this->consultation->havePrivilege(Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::motion($motion))
                ) {
                    return new HtmlResponse($this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]));
                }

                return new BinaryFileResponse(
                    BinaryFileResponse::TYPE_PDF,
                    $section->getData(),
                    false,
                    null,
                    $this->layoutParams->isRobotsIndex($this->action)
                );
            }
        }

        throw new NotFoundHttpException('Not found');
    }

    public function actionEmbeddedpdf(string $file): HtmlResponse
    {
        return new HtmlResponse($this->renderPartial('pdf_embed', ['file' => $file]));
    }

    public function actionPdf(string $motionSlug, ?string $showAlways = null): ResponseInterface
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!($motion->isReadable() || $showAlways === $motion->getShowAlwaysToken())) {
            return new HtmlResponse($this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]));
        }

        $selectedPdfLayout = IPDFLayout::getPdfLayoutForMotionType($motion->getMyMotionType());

        $hasLaTeX = (AntragsgruenApp::getInstance()->xelatexPath || AntragsgruenApp::getInstance()->lualatexPath);
        if (!($hasLaTeX && $selectedPdfLayout->latexId !== null) && $selectedPdfLayout->id === null) {
            return new HtmlErrorResponse(404, \Yii::t('motion', 'err_no_pdf'));
        }

        if ($motion->getAlternativePdfSection()) {
            $pdfData = $motion->getAlternativePdfSection()->getData();
        } elseif ($selectedPdfLayout->isHtmlToPdfLayout()) {
            $pdfData = LayoutHelper::createPdfFromHtml($motion);
        } elseif ($selectedPdfLayout->latexId !== null) {
            $pdfData = LayoutHelper::createPdfLatex($motion);
        } else {
            $pdfData = LayoutHelper::createPdfTcpdf($motion);
        }

        return new BinaryFileResponse(BinaryFileResponse::TYPE_PDF,
            $pdfData,
            false,
            $motion->getFilenameBase(false),
            $this->layoutParams->isRobotsIndex($this->action),
            null
        );
    }

    public function actionPdfamendcollection(string $motionSlug): ResponseInterface
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!$motion->isReadable()) {
            return new HtmlResponse($this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]));
        }

        $amendments = $motion->getVisibleAmendmentsSorted();

        $selectedPdfLayout = IPDFLayout::getPdfLayoutForMotionType($motion->getMyMotionType());
        $hasLaTeX = (AntragsgruenApp::getInstance()->xelatexPath || AntragsgruenApp::getInstance()->lualatexPath);
        if (!($hasLaTeX && $selectedPdfLayout->latexId !== null) && $selectedPdfLayout->id === null) {
            return new HtmlErrorResponse(404, \Yii::t('motion', 'err_no_pdf'));
        }

        if ($selectedPdfLayout->isHtmlToPdfLayout()) {
            $pdfData = $this->renderPartial('pdf_amend_collection_html2pdf', [
                'motion' => $motion, 'amendments' => $amendments
            ]);
        } elseif ($selectedPdfLayout->latexId !== null) {
            $pdfData = $this->renderPartial('pdf_amend_collection_tex', [
                'motion' => $motion, 'amendments' => $amendments, 'texTemplate' => $motion->motionType->texTemplate
            ]);
        } else {
            $pdfData = $this->renderPartial('pdf_amend_collection_tcpdf', [
                'motion' => $motion, 'amendments' => $amendments
            ]);
        }

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_PDF,
            $pdfData,
            false,
            $motion->getFilenameBase(false) . '.collection',
            $this->layoutParams->isRobotsIndex($this->action)
        );
    }

    private function getMotionsAndTemplate(string $motionTypeId, bool $inactive, bool $resolutions): array
    {
        /** @var TexTemplate $texTemplate */
        $texTemplate = null;

        $search = AdminMotionFilterForm::getForConsultationFromRequest(
            $this->consultation,
            $this->consultation->motions,
            $this->getRequestValue('Search')
        );

        $imotions = $search->getMotionsForExport($this->consultation, $motionTypeId, $inactive);

        $imotionsFiltered = [];
        foreach ($imotions as $imotion) {
            if ($resolutions && !$imotion->isResolution()) {
                continue;
            }
            if ($texTemplate === null) {
                $texTemplate       = $imotion->getMyMotionType()->texTemplate;
                $imotionsFiltered[] = $imotion;
            } elseif ($imotion->getMyMotionType()->texTemplate && $imotion->getMyMotionType()->texTemplate->id === $texTemplate->id) {
                $imotionsFiltered[] = $imotion;
            }
        }

        return [$imotionsFiltered, $texTemplate];
    }

    public function actionFullpdf(string $motionTypeId = '', int $inactive = 0, int $resolutions = 0): ResponseInterface
    {
        try {
            list($imotions, $texTemplate) = $this->getMotionsAndTemplate($motionTypeId, ($inactive === 1), ($resolutions === 1));
            /** @var IMotion[] $imotions */
            if (count($imotions) === 0) {
                return new HtmlErrorResponse(404, \Yii::t('motion', 'none_yet'));
            }
            // Hint: If it is an amendmentOnly type, we will include the base motion here, too. Hence, no differentiation.
        } catch (ExceptionBase $e) {
            return new HtmlErrorResponse(404, $e->getMessage());
        }

        $selectedPdfLayout = IPDFLayout::getPdfLayoutForMotionType($imotions[0]->getMyMotionType());
        $hasLaTeX = (AntragsgruenApp::getInstance()->xelatexPath || AntragsgruenApp::getInstance()->lualatexPath);
        if (!($hasLaTeX && $selectedPdfLayout->latexId !== null) && $selectedPdfLayout->id === null) {
            return new HtmlErrorResponse(404, \Yii::t('motion', 'err_no_pdf'));
        }

        if ($selectedPdfLayout->isHtmlToPdfLayout()) {
            $pdfData = $this->renderPartial('pdf_full_html2pdf', ['imotions' => $imotions]);
        } elseif ($selectedPdfLayout->latexId !== null) {
            $pdfData = $this->renderPartial('pdf_full_tex', ['imotions' => $imotions, 'texTemplate' => $texTemplate]);
        } else {
            $pdfData = $this->renderPartial('pdf_full_tcpdf', ['imotions' => $imotions]);
        }

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_PDF,
            $pdfData,
            false,
            null,
            $this->layoutParams->isRobotsIndex($this->action)
        );
    }

    public function actionPdfcollection(string $motionTypeId = '', int $inactive = 0, int $resolutions = 0): ResponseInterface
    {
        try {
            list($imotions, $texTemplate) = $this->getMotionsAndTemplate($motionTypeId, ($inactive === 1), ($resolutions === 1));
            if (count($imotions) === 0) {
                return new HtmlErrorResponse(404, \Yii::t('motion', 'none_yet'));
            }
            /** @var IMotion[] $imotions */
            $motionType = $imotions[0]->getMyMotionType();
            if ($motionType->amendmentsOnly) {
                $imotions = [];
                foreach ($motionType->motions as $motion) {
                    if (is_a($motion, Motion::class)) {
                        $filter = IMotionStatusFilter::adminExport($this->consultation, ($inactive === 1));
                        $imotions = array_merge($imotions, $motion->getFilteredAndSortedAmendments($filter));
                    }
                }
                if (count($imotions) === 0) {
                    return new HtmlErrorResponse(404, \Yii::t('motion', 'none_yet'));
                }
            }
        } catch (ExceptionBase $e) {
            return new HtmlErrorResponse(404, $e->getMessage());
        }

        $selectedPdfLayout = IPDFLayout::getPdfLayoutForMotionType($motionType);

        $hasLaTeX = (AntragsgruenApp::getInstance()->xelatexPath || AntragsgruenApp::getInstance()->lualatexPath);
        if (!($hasLaTeX && $selectedPdfLayout->latexId !== null) && $selectedPdfLayout->id === null) {
            return new HtmlErrorResponse(404, \Yii::t('motion', 'err_no_pdf'));
        }

        if ($selectedPdfLayout->isHtmlToPdfLayout()) {
            $pdfData = $this->renderPartial('pdf_collection_html2pdf', ['imotions' => $imotions]);
        } elseif ($selectedPdfLayout->latexId !== null) {
            $pdfData = $this->renderPartial('pdf_collection_tex', ['imotions' => $imotions, 'texTemplate' => $texTemplate]);
        } else {
            $pdfData = $this->renderPartial('pdf_collection_tcpdf', ['imotions' => $imotions]);
        }

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_PDF,
            $pdfData,
            false,
            Tools::sanitizeFilename($motionType->titlePlural, false),
            $this->layoutParams->isRobotsIndex($this->action)
        );
    }

    public function actionEmbeddedAmendmentsPdf(string $motionSlug): ResponseInterface
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!$motion->isReadable()) {
            return new HtmlResponse($this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]));
        }

        $form = Init::forEmbeddedAmendmentsExport($motion);

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_PDF,
            $this->renderPartial('pdf_embedded_amendments_tcpdf', ['form' => $form]),
            false,
            null,
            false
        );
    }

    public function actionOdt(string $motionSlug): ResponseInterface
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!$motion->isReadable()) {
            return new HtmlResponse($this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]));
        }

        $doc = $motion->getMyMotionType()->createOdtTextHandler();
        LayoutHelper::printMotionToOdt($motion, $doc);
        $odtData = $doc->finishAndGetDocument();

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_ODT,
            $odtData,
            true,
            $motion->getFilenameBase(false),
            false
        );
    }

    public function actionPlainhtml(string $motionSlug): ResponseInterface
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!$motion->isReadable()) {
            return new HtmlResponse($this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]));
        }

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_HTML,
            $this->renderPartial('plain_html', ['motion' => $motion]),
            false,
            null,
            $this->layoutParams->isRobotsIndex($this->action)
        );
    }

    public function actionRest(string $motionSlug, ?string $lineNumbers = null): RestApiResponse
    {
        $this->handleRestHeaders(['GET']);

        $lineNumbers = ($lineNumbers !== null && in_array(strtolower($lineNumbers), ['true', '1']));

        try {
            $motion = $this->getMotionWithCheck($motionSlug, true);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        if (!$motion->isReadable()) {
            return $this->returnRestResponseFromException(new NotFound('Motion is not readable'));
        }

        return new RestApiResponse(200, null, $this->renderPartial('rest_get', [
            'motion' => $motion,
            'lineNumbers' => $lineNumbers,
        ]));
    }

    public function actionViewChangesOdt(string $motionSlug): ResponseInterface
    {
        $motion       = $this->getMotionWithCheck($motionSlug);
        $parentMotion = $motion->replacedMotion;

        if (!$motion->isReadable() && !$motion->canMergeAmendments()) {
            return new HtmlResponse($this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]));
        }
        if (!$parentMotion || !$parentMotion->isReadable()) {
            RequestContext::getSession()->setFlash('error', 'The diff-view is not available');
            return new RedirectResponse(UrlHelper::createMotionUrl($motion));
        }

        try {
            $changes = MotionSectionChanges::motionToSectionChanges($parentMotion, $motion);
        } catch (\Exception $e) {
            return new HtmlErrorResponse(500, $e->getMessage());
        }

        $odtData = $this->renderPartial('view_changes_odt', [
            'oldMotion' => $parentMotion,
            'changes'   => $changes,
        ]);
        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_ODT,
            $odtData,
            true,
            $motion->getFilenameBase(false) . '-changes',
            false
        );
    }
}
