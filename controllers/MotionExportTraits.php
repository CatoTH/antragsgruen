<?php

namespace app\controllers;

use app\components\Tools;
use app\components\UrlHelper;
use app\models\exceptions\NotFound;
use app\models\mergeAmendments\Init;
use app\models\db\{Amendment, Consultation, ConsultationUserGroup, IMotion, Motion, TexTemplate, User};
use app\models\exceptions\ExceptionBase;
use app\models\MotionSectionChanges;
use app\models\settings\AntragsgruenApp;
use app\views\motion\LayoutHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\Session;

/**
 * @property \Yii\base\Action $action
 * @property Consultation $consultation
 * @property \app\models\settings\Layout $layoutParams
 *
 * @method string render(string $template, array $params)
 * @method string redirect(string $url)
 * @method string renderPartial(string $template, array $params)
 * @method AntragsgruenApp getParams()
 * @method Session getHttpSession()
 * @method string showErrorpage(int $error, string $message)
 */
trait MotionExportTraits
{
    /**
     * @param string $motionSlug
     * @param int $sectionId
     * @param null|string $showAlways
     * @return string
     */
    public function actionViewimage($motionSlug, $sectionId, $showAlways = null)
    {
        $motion    = $this->getMotionWithCheck($motionSlug);
        $sectionId = IntVal($sectionId);

        foreach ($motion->getActiveSections() as $section) {
            if ($section->sectionId === $sectionId) {
                if (!$motion->isReadable() && $section->getShowAlwaysToken() !== $showAlways &&
                    !$this->consultation->havePrivilege(ConsultationUserGroup::PRIVILEGE_SCREENING)
                ) {
                    return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
                }
                if ($section->getSectionType()->isEmpty()) {
                    return $this->showErrorpage(404, 'Image not found');
                }

                $metadata                    = json_decode($section->metadata, true);
                \Yii::$app->response->format = Response::FORMAT_RAW;
                \Yii::$app->response->headers->add('Content-Type', $metadata['mime']);
                if (!$this->layoutParams->isRobotsIndex($this->action)) {
                    \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
                }
                return $section->getData();
            }
        }
        return $this->showErrorpage(404, 'Image not found');
    }

    /**
     * @param string $motionSlug
     * @param int $sectionId
     * @param string|null $showAlways
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionViewpdf($motionSlug, $sectionId, $showAlways = null)
    {
        $motion    = $this->getMotionWithCheck($motionSlug);
        $sectionId = intval($sectionId);

        foreach ($motion->getActiveSections() as $section) {
            if ($section->sectionId === $sectionId) {
                if (!$motion->isReadable() && $section->getShowAlwaysToken() !== $showAlways &&
                    !$this->consultation->havePrivilege(ConsultationUserGroup::PRIVILEGE_SCREENING)
                ) {
                    return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
                }

                \Yii::$app->response->format = Response::FORMAT_RAW;
                \Yii::$app->response->headers->add('Content-Type', 'application/pdf');
                \Yii::$app->response->headers->add('Content-Disposition', 'inline');
                if (!$this->layoutParams->isRobotsIndex($this->action)) {
                    \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
                }
                return $section->getData();
            }
        }

        throw new NotFoundHttpException('Not found');
    }

    /**
     * @param string $file
     * @return string
     */
    public function actionEmbeddedpdf($file)
    {
        return $this->renderPartial('pdf_embed', ['file' => $file]);
    }

    /**
     * @param string $motionSlug
     * @return string
     * @throws \Exception
     */
    public function actionPdf($motionSlug)
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!$motion->isReadable()) {
            return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
        }

        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        if (!($hasLaTeX && $motion->getMyMotionType()->texTemplateId) && !$motion->getMyMotionType()->getPDFLayoutClass()) {
            $this->showErrorpage(404, \Yii::t('motion', 'err_no_pdf'));
            return '';
        }

        $filename                    = $motion->getFilenameBase(false) . '.pdf';
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/pdf');
        \Yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        if ($motion->getAlternativePdfSection()) {
            return $motion->getAlternativePdfSection()->getData();
        }

        if ($hasLaTeX && $motion->getMyMotionType()->texTemplateId) {
            return LayoutHelper::createPdfLatex($motion);
        } else {
            return LayoutHelper::createPdfTcpdf($motion);
        }
    }

    /**
     * @param string $motionSlug
     * @return string
     */
    public function actionPdfamendcollection($motionSlug)
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!$motion->isReadable()) {
            return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
        }

        $amendments = $motion->getVisibleAmendmentsSorted();

        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        if (!($hasLaTeX && $motion->getMyMotionType()->texTemplateId) && !$motion->getMyMotionType()->getPDFLayoutClass()) {
            $this->showErrorpage(404, \Yii::t('motion', 'err_no_pdf'));
            return '';
        }

        $filename                    = $motion->getFilenameBase(false) . '.collection.pdf';
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/pdf');
        \Yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        if ($hasLaTeX && $motion->getMyMotionType()->texTemplateId) {
            return $this->renderPartial('pdf_amend_collection_tex', [
                'motion' => $motion, 'amendments' => $amendments, 'texTemplate' => $motion->motionType->texTemplate
            ]);
        } else {
            return $this->renderPartial('pdf_amend_collection_tcpdf', [
                'motion' => $motion, 'amendments' => $amendments
            ]);
        }
    }

    private function getMotionsAndTemplate(string $motionTypeId, bool $withdrawn, bool $resolutions)
    {
        /** @var TexTemplate $texTemplate */
        $texTemplate = null;
        $imotions = $this->consultation->getVisibleIMotionsSorted($withdrawn);
        if ($motionTypeId !== '' && $motionTypeId !== '0') {
            $motionTypeIds = explode(',', $motionTypeId);
            $imotions       = array_filter($imotions, function (IMotion $motion) use ($motionTypeIds) {
                if (is_a($motion, Motion::class)) {
                    $motionTypeId = $motion->motionTypeId;
                } else {
                    /** @var Amendment $motion */
                    $motionTypeId = $motion->getMyMotion()->motionTypeId;
                }
                return in_array($motionTypeId, $motionTypeIds);
            });
        }

        $imotionsFiltered = [];
        foreach ($imotions as $imotion) {
            $resolutionStates = [Motion::STATUS_RESOLUTION_FINAL, Motion::STATUS_RESOLUTION_PRELIMINARY];
            if ($resolutions && !in_array($imotion->status, $resolutionStates)) {
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

    public function actionFullpdf($motionTypeId = '', $withdrawn = 0, $resolutions = 0)
    {
        $withdrawn   = (intval($withdrawn) === 1);
        $resolutions = (intval($resolutions) === 1);

        try {
            list($imotions, $texTemplate) = $this->getMotionsAndTemplate($motionTypeId, $withdrawn, $resolutions);
            /** @var IMotion[] $imotions */
            if (count($imotions) === 0) {
                return $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
            }
            // Hint: If it is an amendmentOnly type, we will include the base motion here, too. Hence, no differentiation.
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }

        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        if (!($hasLaTeX && $texTemplate) && !$imotions[0]->getMyMotionType()->getPDFLayoutClass()) {
            $this->showErrorpage(404, \Yii::t('motion', 'err_no_pdf'));
            return '';
        }

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/pdf');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        if ($hasLaTeX && $texTemplate) {
            return $this->renderPartial('pdf_full_tex', ['imotions' => $imotions, 'texTemplate' => $texTemplate]);
        } else {
            return $this->renderPartial('pdf_full_tcpdf', ['imotions' => $imotions]);
        }
    }

    /**
     * @param string $motionTypeId
     * @param int $withdrawn
     * @param int $resolutions
     * @return string
     */
    public function actionPdfcollection($motionTypeId = '', $withdrawn = 0, $resolutions = 0)
    {
        $withdrawn   = (intval($withdrawn) === 1);
        $resolutions = (intval($resolutions) === 1);

        try {
            list($imotions, $texTemplate) = $this->getMotionsAndTemplate($motionTypeId, $withdrawn, $resolutions);
            if (count($imotions) === 0) {
                return $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
            }
            /** @var IMotion[] $imotions */
            $motionType = $imotions[0]->getMyMotionType();
            if ($motionType->amendmentsOnly) {
                $imotions = [];
                foreach ($motionType->motions as $motion) {
                    if (is_a($motion, Motion::class)) {
                        $imotions = array_merge($imotions, $motion->getVisibleAmendmentsSorted($withdrawn));
                    }
                }
                if (count($imotions) === 0) {
                    return $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
                }
            }
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }

        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        if (!($hasLaTeX && $texTemplate) && !$motionType->getPDFLayoutClass()) {
            $this->showErrorpage(404, \Yii::t('motion', 'err_no_pdf'));
            return '';
        }

        $filename = Tools::sanitizeFilename($motionType->titlePlural, false) . '.pdf';
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/pdf');
        \Yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        if ($hasLaTeX && $texTemplate) {
            return $this->renderPartial('pdf_collection_tex', ['imotions' => $imotions, 'texTemplate' => $texTemplate]);
        } else {
            return $this->renderPartial('pdf_collection_tcpdf', ['imotions' => $imotions]);
        }
    }

    public function actionEmbeddedAmendmentsPdf(string $motionSlug): string
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!$motion->isReadable()) {
            return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
        }

        $form = Init::forEmbeddedAmendmentsExport($motion);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/pdf');
        \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $this->renderPartial('pdf_embedded_amendments_tcpdf', ['form' => $form]);
    }

    /**
     * @param string $motionSlug
     * @return string
     */
    public function actionOdt($motionSlug)
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!$motion->isReadable()) {
            return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
        }

        $filename                    = $motion->getFilenameBase(false) . '.odt';
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.text');
        \Yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $this->renderPartial('view_odt', ['motion' => $motion]);
    }

    /**
     * @param string $motionSlug
     * @return string
     */
    public function actionPlainhtml($motionSlug)
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!$motion->isReadable()) {
            return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
        }
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $this->renderPartial('plain_html', ['motion' => $motion]);
    }

    /**
     * @param string $motionSlug
     * @param ?string|null $lineNumbers
     * @return string
     */
    public function actionRest($motionSlug, ?string $lineNumbers = null)
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

        return $this->returnRestResponse(200, $this->renderPartial('rest_get', [
            'motion' => $motion,
            'lineNumbers' => $lineNumbers,
        ]));
    }

    /**
     * @param string $motionSlug
     * @return string
     */
    public function actionViewChangesOdt($motionSlug)
    {
        $motion       = $this->getMotionWithCheck($motionSlug);
        $parentMotion = $motion->replacedMotion;

        if (!$motion->isReadable()) {
            return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
        }
        if (!$parentMotion || !$parentMotion->isReadable()) {
            $this->getHttpSession()->setFlash('error', 'The diff-view is not available');
            return $this->redirect(UrlHelper::createMotionUrl($motion));
        }

        $filename                    = $motion->getFilenameBase(false) . '-changes.odt';
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.text');
        \Yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        try {
            $changes = MotionSectionChanges::motionToSectionChanges($parentMotion, $motion);
        } catch (\Exception $e) {
            return $this->showErrorpage(500, $e->getMessage());
        }

        return $this->renderPartial('view_changes_odt', [
            'oldMotion' => $parentMotion,
            'changes'   => $changes,
        ]);
    }
}
