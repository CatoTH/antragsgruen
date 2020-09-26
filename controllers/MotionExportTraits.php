<?php

namespace app\controllers;

use app\components\UrlHelper;
use app\models\exceptions\NotFound;
use app\models\db\{Consultation, Motion, TexTemplate, User};
use app\models\exceptions\ExceptionBase;
use app\models\MotionSectionChanges;
use app\models\settings\AntragsgruenApp;
use app\views\motion\LayoutHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * @property \Yii\base\Action $action
 * @property Consultation $consultation
 * @property \app\models\settings\Layout $layoutParams
 *
 * @method string render(string $template, array $params)
 * @method string redirect(string $url)
 * @method string renderPartial(string $template, array $params)
 * @method AntragsgruenApp getParams()
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
                    !User::havePrivilege($this->consultation, User::PRIVILEGE_SCREENING)
                ) {
                    return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
                }
                if ($section->getSectionType()->isEmpty()) {
                    return $this->showErrorpage(404, 'Image not found');
                }

                $metadata                    = json_decode($section->metadata, true);
                \yii::$app->response->format = Response::FORMAT_RAW;
                \yii::$app->response->headers->add('Content-Type', $metadata['mime']);
                if (!$this->layoutParams->isRobotsIndex($this->action)) {
                    \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
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
                    !User::havePrivilege($this->consultation, User::PRIVILEGE_SCREENING)
                ) {
                    return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
                }

                \yii::$app->response->format = Response::FORMAT_RAW;
                \yii::$app->response->headers->add('Content-Type', 'application/pdf');
                if (!$this->layoutParams->isRobotsIndex($this->action)) {
                    \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
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
     * @param null|string $showAlways
     * @return string
     * @throws \Exception
     */
    public function actionPdf($motionSlug, $showAlways = null)
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!$motion->isReadable()) {
            return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
        }

        $filename                    = $motion->getFilenameBase(false) . '.pdf';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');
        \yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        if ($motion->getAlternativePdfSection()) {
            return $motion->getAlternativePdfSection()->getData();
        }

        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
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

        $filename                    = $motion->getFilenameBase(false) . '.collection.pdf';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');
        \yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
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
        $motions     = $this->consultation->getVisibleMotionsSorted($withdrawn);
        if ($motionTypeId !== '' && $motionTypeId !== '0') {
            $motionTypeIds = explode(',', $motionTypeId);
            $motions       = array_filter($motions, function (Motion $motion) use ($motionTypeIds) {
                return in_array($motion->motionTypeId, $motionTypeIds);
            });
        }

        $motionsFiltered = [];
        foreach ($motions as $motion) {
            $resolutionStates = [Motion::STATUS_RESOLUTION_FINAL, Motion::STATUS_RESOLUTION_PRELIMINARY];
            if ($resolutions && !in_array($motion->status, $resolutionStates)) {
                continue;
            }
            if ($texTemplate === null) {
                $texTemplate       = $motion->motionType->texTemplate;
                $motionsFiltered[] = $motion;
            } elseif ($motion->motionType->texTemplate && $motion->motionType->texTemplate->id === $texTemplate->id) {
                $motionsFiltered[] = $motion;
            }
        }
        $motions = $motionsFiltered;

        return [$motions, $texTemplate];
    }

    public function actionFullpdf($motionTypeId = '', $withdrawn = 0, $resolutions = 0)
    {
        $withdrawn   = (IntVal($withdrawn) === 1);
        $resolutions = (IntVal($resolutions) === 1);

        try {
            list($motions, $texTemplate) = $this->getMotionsAndTemplate($motionTypeId, $withdrawn, $resolutions);
            if (count($motions) === 0) {
                return $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
            }
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        if ($hasLaTeX && $texTemplate) {
            return $this->renderPartial('pdf_full_tex', ['motions' => $motions, 'texTemplate' => $texTemplate]);
        } else {
            return $this->renderPartial('pdf_full_tcpdf', ['motions' => $motions]);
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
        $withdrawn   = (IntVal($withdrawn) === 1);
        $resolutions = (IntVal($resolutions) === 1);

        try {
            list($motions, $texTemplate) = $this->getMotionsAndTemplate($motionTypeId, $withdrawn, $resolutions);
            if (count($motions) === 0) {
                return $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
            }
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        if ($hasLaTeX && $texTemplate) {
            return $this->renderPartial('pdf_collection_tex', ['motions' => $motions, 'texTemplate' => $texTemplate]);
        } else {
            return $this->renderPartial('pdf_collection_tcpdf', ['motions' => $motions]);
        }
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
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.text');
        \yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
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
            \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $this->renderPartial('plain_html', ['motion' => $motion]);
    }

    /**
     * @param string $motionSlug
     * @return string
     */
    public function actionRest($motionSlug)
    {
        $this->handleRestHeaders();

        try {
            $motion = $this->getMotionWithCheck($motionSlug, true);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        if (!$motion->isReadable()) {
            return $this->returnRestResponseFromException(new NotFound('Motion is not readable'));
        }

        return $this->returnRestResponse(200, $this->renderPartial('rest_get', ['motion' => $motion]));
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
            \Yii::$app->session->setFlash('error', 'The diff-view is not available');
            return $this->redirect(UrlHelper::createMotionUrl($motion));
        }

        $filename                    = $motion->getFilenameBase(false) . '-changes.odt';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.text');
        \yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
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
