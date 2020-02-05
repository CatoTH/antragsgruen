<?php

namespace app\controllers;

use app\components\{Tools, UrlHelper, EmailNotifications};
use app\models\db\{Amendment,
    ConsultationAgendaItem,
    ConsultationLog,
    ConsultationMotionType,
    ConsultationSettingsMotionSection,
    IMotion,
    Motion,
    MotionAdminComment,
    MotionSupporter,
    User,
    UserNotification,
    VotingBlock};
use app\models\exceptions\{ExceptionBase, FormError, Inconsistency, Internal, MailNotSent};
use app\models\forms\MotionEditForm;
use app\models\sectionTypes\ISectionType;
use app\models\MotionSectionChanges;
use app\models\notifications\MotionProposedProcedure;
use app\models\events\MotionEvent;
use app\views\motion\LayoutHelper;
use yii\web\{NotFoundHttpException, Response};

class MotionController extends Base
{
    use MotionActionsTrait;
    use MotionMergingTrait;

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

                $metadata                    = json_decode($section->metadata, true);
                \yii::$app->response->format = Response::FORMAT_RAW;
                \yii::$app->response->headers->add('Content-Type', $metadata['mime']);
                if (!$this->layoutParams->isRobotsIndex($this->action)) {
                    \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
                }
                return base64_decode($section->data);
            }
        }
        return '';
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
        $sectionId = IntVal($sectionId);

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
                return base64_decode($section->data);
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
     * @return Motion|null
     */
    private function getMotionWithCheck($motionSlug)
    {
        if (is_numeric($motionSlug) && $motionSlug > 0) {
            $motion = Motion::findOne([
                'consultationId' => $this->consultation->id,
                'id'             => $motionSlug,
                'slug'           => null
            ]);
        } else {
            $motion = Motion::findOne([
                'consultationId' => $this->consultation->id,
                'slug'           => $motionSlug
            ]);
        }
        /** @var Motion $motion */
        if (!$motion) {
            $redirect = $this->guessRedirectByPrefix($motionSlug);
            if ($redirect) {
                $this->redirect($redirect);
            } else {
                \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));
                $this->redirect(UrlHelper::createUrl('consultation/index'));
            }
            \Yii::$app->end();
            return null;
        }

        $this->checkConsistency($motion);

        return $motion;
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
            return base64_decode($motion->getAlternativePdfSection()->data);
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
        $texTemplate = null;
        try {
            $motions = $this->consultation->getVisibleMotionsSorted($withdrawn);
            if ($motionTypeId != '' && $motionTypeId != '0') {
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
                } elseif ($motion->motionType->texTemplate == $texTemplate) {
                    $motionsFiltered[] = $motion;
                }
            }
            $motions = $motionsFiltered;

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
     * @param int $commentId
     * @return string
     * @throws Internal
     */
    public function actionView($motionSlug, $commentId = 0)
    {
        $this->layout = 'column2';

        $motion = $this->getMotionWithCheck($motionSlug);
        if (User::havePrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            $adminEdit = UrlHelper::createUrl(['admin/motion/update', 'motionId' => $motion->id]);
        } else {
            $adminEdit = null;
        }

        if (!$motion->isReadable()) {
            return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => $adminEdit]);
        }

        $openedComments = [];
        if ($commentId > 0) {
            foreach ($motion->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                foreach ($section->getTextParagraphObjects(false, true, true) as $paragraph) {
                    foreach ($paragraph->comments as $comment) {
                        if ($comment->id == $commentId) {
                            if (!isset($openedComments[$section->sectionId])) {
                                $openedComments[$section->sectionId] = [];
                            }
                            $openedComments[$section->sectionId][] = $paragraph->paragraphNo;
                        }
                    }
                }
            }
        }


        $commentWholeMotions = false;
        foreach ($motion->getActiveSections() as $section) {
            if ($section->getSettings()->hasComments == ConsultationSettingsMotionSection::COMMENTS_MOTION) {
                $commentWholeMotions = true;
            }
        }

        $motionViewParams = [
            'motion'              => $motion,
            'openedComments'      => $openedComments,
            'adminEdit'           => $adminEdit,
            'commentForm'         => null,
            'commentWholeMotions' => $commentWholeMotions,
        ];

        try {
            $this->performShowActions($motion, $commentId, $motionViewParams);
        } catch (\Exception $e) {
            \yii::$app->session->setFlash('error', $e->getMessage());
        }

        $supportStatus = '';
        if (!\Yii::$app->user->isGuest) {
            foreach ($motion->motionSupporters as $supp) {
                if ($supp->userId === User::getCurrentUser()->id) {
                    $supportStatus = $supp->role;
                }
            }
        }
        $motionViewParams['supportStatus'] = $supportStatus;


        return $this->render('view', $motionViewParams);
    }

    /**
     * @param string $motionSlug
     * @return string
     */
    public function actionViewChanges($motionSlug)
    {
        $this->layout = 'column2';

        $motion       = $this->getMotionWithCheck($motionSlug);
        $parentMotion = $motion->replacedMotion;

        if (!$motion->isReadable()) {
            return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
        }
        if (!$parentMotion || !$parentMotion->isReadable()) {
            \Yii::$app->session->setFlash('error', 'The diff-view is not available');
            return $this->redirect(UrlHelper::createMotionUrl($motion));
        }

        try {
            $changes = MotionSectionChanges::motionToSectionChanges($parentMotion, $motion);
        } catch (Inconsistency $e) {
            $changes = [];
            \Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->render('view_changes', [
            'newMotion' => $motion,
            'oldMotion' => $parentMotion,
            'changes'   => $changes,
        ]);
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

    /**
     * @param string $motionSlug
     * @param string $fromMode
     * @return string
     */
    public function actionCreatedone($motionSlug, $fromMode)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        return $this->render('create_done', ['motion' => $motion, 'mode' => $fromMode]);
    }

    /**
     * @param string $motionSlug
     * @param string $fromMode
     * @return string
     * @throws Internal
     */
    public function actionCreateconfirm($motionSlug, $fromMode)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion || $motion->status !== Motion::STATUS_DRAFT) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if ($this->isPostSet('modify')) {
            return $this->redirect(UrlHelper::createMotionUrl($motion, 'edit'));
        }

        if ($this->isPostSet('confirm')) {
            $motion->trigger(Motion::EVENT_SUBMITTED, new MotionEvent($motion));

            if ($motion->status === Motion::STATUS_SUBMITTED_SCREENED) {
                $motion->trigger(Motion::EVENT_PUBLISHED, new MotionEvent($motion));
            } else {
                EmailNotifications::sendMotionSubmissionConfirm($motion);
            }

            if (User::getCurrentUser()) {
                UserNotification::addNotification(
                    User::getCurrentUser(),
                    $this->consultation,
                    UserNotification::NOTIFICATION_AMENDMENT_MY_MOTION
                );
            }

            return $this->redirect(UrlHelper::createMotionUrl($motion, 'createdone', ['fromMode' => $fromMode]));
        } else {
            $params                  = ['motion' => $motion, 'mode' => $fromMode];
            $params['deleteDraftId'] = $this->getRequestValue('draftId');
            return $this->render('create_confirm', $params);
        }
    }

    /**
     * @param string $motionSlug
     * @return string
     * @throws FormError
     */
    public function actionEdit($motionSlug)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (!$motion->canEdit()) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_edit_permission'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        $form     = new MotionEditForm($motion->motionType, $motion->agendaItem, $motion);
        $fromMode = ($motion->status == Motion::STATUS_DRAFT ? 'create' : 'edit');

        if ($this->isPostSet('save')) {
            $post = \Yii::$app->request->post();
            $motion->flushCache(true);
            $form->setAttributes([$post, $_FILES]);
            try {
                $form->saveMotion($motion);
                if (isset($post['sections'])) {
                    $form->updateTextRewritingAmendments($motion, $post['sections']);
                }

                if ($motion->isVisible()) {
                    ConsultationLog::logCurrUser($this->consultation, ConsultationLog::MOTION_CHANGE, $motion->id);
                }

                if ($motion->status == Motion::STATUS_DRAFT) {
                    $nextUrl = UrlHelper::createMotionUrl($motion, 'createconfirm', ['fromMode' => $fromMode]);
                    return $this->redirect($nextUrl);
                } else {
                    return $this->render('edit_done', ['motion' => $motion]);
                }
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
                $form->setSectionTextWithoutSaving($motion, $post['sections']);
            }
        }

        return $this->render(
            'edit_form',
            [
                'mode'         => $fromMode,
                'form'         => $form,
                'consultation' => $this->consultation,
            ]
        );
    }

    /**
     * @param int $motionTypeId
     * @param int $agendaItemId
     * @param int $cloneFrom
     * @return array
     * @throws Internal
     * @throws \app\models\exceptions\NotFound
     */
    private function getMotionTypeForCreate($motionTypeId = 0, $agendaItemId = 0, $cloneFrom = 0)
    {
        if ($agendaItemId > 0) {
            $where      = ['consultationId' => $this->consultation->id, 'id' => $agendaItemId];
            $agendaItem = ConsultationAgendaItem::findOne($where);
            if (!$agendaItem) {
                throw new Internal('Could not find agenda item');
            }
            /** @var ConsultationAgendaItem $agendaItem */
            if (!$agendaItem->motionType) {
                throw new Internal('Agenda item does not have motions');
            }
            $motionType = $agendaItem->motionType;
        } elseif ($motionTypeId > 0) {
            $motionType = $this->consultation->getMotionType($motionTypeId);
            $agendaItem = null;
        } elseif ($cloneFrom > 0) {
            $motion = $this->consultation->getMotion($cloneFrom);
            if (!$motion) {
                throw new Internal('Could not find referenced motion');
            }
            $motionType = $motion->motionType;
            $agendaItem = $motion->agendaItem;
        } else {
            throw new Internal('Could not resolve motion type');
        }

        return [$motionType, $agendaItem];
    }


    /**
     * @param int $motionTypeId
     * @param int $agendaItemId
     * @param int $cloneFrom
     * @return string
     * @throws Internal
     * @throws \yii\base\Exception
     */
    public function actionCreate($motionTypeId = 0, $agendaItemId = 0, $cloneFrom = 0)
    {
        try {
            $ret = $this->getMotionTypeForCreate($motionTypeId, $agendaItemId, $cloneFrom);
            list($motionType, $agendaItem) = $ret;
        } catch (ExceptionBase $e) {
            \Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        /**
         * @var ConsultationMotionType $motionType
         * @var ConsultationAgendaItem|null $agendaItem
         */

        $policy = $motionType->getMotionPolicy();
        if (!$policy->checkCurrUserMotion()) {
            if ($policy->checkCurrUserMotion(true, true)) {
                $loginUrl = UrlHelper::createLoginUrl([
                    'motion/create',
                    'motionTypeId' => $motionTypeId,
                    'agendaItemId' => $agendaItemId
                ]);
                return $this->redirect($loginUrl);
            } else {
                return $this->showErrorpage(403, \Yii::t('motion', 'err_create_permission'));
            }
        }

        $form        = new MotionEditForm($motionType, $agendaItem, null);
        $supportType = $motionType->getMotionSupportTypeClass();
        $iAmAdmin    = User::havePrivilege($this->consultation, User::PRIVILEGE_SCREENING);

        if ($this->isPostSet('save')) {
            try {
                $motion = $form->createMotion();

                // Supporting members are not collected in the form, but need to be copied a well
                if ($supportType->collectSupportersBeforePublication() && $cloneFrom && $iAmAdmin) {
                    $adoptMotion = $this->consultation->getMotion($cloneFrom);
                    foreach ($adoptMotion->getSupporters() as $supp) {
                        $suppNew = new MotionSupporter();
                        $suppNew->setAttributes($supp->getAttributes());
                        $suppNew->id           = null;
                        $suppNew->motionId     = $motion->id;
                        $suppNew->extraData    = $supp->extraData;
                        $suppNew->dateCreation = date('Y-m-d H:i:s');
                        $suppNew->save();
                    }
                }

                return $this->redirect(UrlHelper::createMotionUrl($motion, 'createconfirm', [
                    'fromMode' => 'create',
                    'draftId'  => $this->getRequestValue('draftId'),
                ]));
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }
        } elseif ($cloneFrom > 0) {
            $motion = $this->consultation->getMotion($cloneFrom);
            $form->cloneSupporters($motion);
            $form->cloneMotionText($motion);
        }


        if (count($form->supporters) === 0) {
            $supporter               = new MotionSupporter();
            $supporter->role         = MotionSupporter::ROLE_INITIATOR;
            $supporter->dateCreation = date('Y-m-d H:i:s');
            $iAmAdmin                = User::havePrivilege($this->consultation, User::PRIVILEGE_SCREENING);
            if (User::getCurrentUser() && !$iAmAdmin) {
                $user                    = User::getCurrentUser();
                $supporter->userId       = $user->id;
                $supporter->name         = $user->name;
                $supporter->contactEmail = $user->email;
                $supporter->personType   = MotionSupporter::PERSON_NATURAL;
            }
            $form->supporters[] = $supporter;
        }

        return $this->render(
            'edit_form',
            [
                'mode'         => 'create',
                'form'         => $form,
                'consultation' => $this->consultation,
            ]
        );
    }


    /**
     * @param string $motionSlug
     * @return string
     */
    public function actionWithdraw($motionSlug)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (!$motion->canWithdraw()) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_withdraw_permission'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if ($this->isPostSet('cancel')) {
            return $this->redirect(UrlHelper::createMotionUrl($motion));
        }

        if ($this->isPostSet('withdraw')) {
            $motion->withdraw();
            \Yii::$app->session->setFlash('success', \Yii::t('motion', 'withdraw_done'));
            return $this->redirect(UrlHelper::createMotionUrl($motion));
        }

        return $this->render('withdraw', ['motion' => $motion]);
    }

    /**
     * @param string $motionSlug
     * @return string
     * @throws Internal
     */
    public function actionSaveProposalStatus($motionSlug)
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            \Yii::$app->response->statusCode = 404;
            return 'Motion not found';
        }
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CHANGE_PROPOSALS)) {
            \Yii::$app->response->statusCode = 403;
            return 'Not permitted to change the status';
        }

        $response = [];
        $msgAlert = null;

        if (\Yii::$app->request->post('setStatus', null) !== null) {
            $setStatus = IntVal(\Yii::$app->request->post('setStatus'));
            if ($motion->proposalStatus !== $setStatus) {
                if ($motion->proposalUserStatus !== null) {
                    $msgAlert = \Yii::t('amend', 'proposal_user_change_reset');
                }
                $motion->proposalUserStatus = null;
            }
            $motion->proposalStatus  = $setStatus;
            $motion->proposalComment = \Yii::$app->request->post('proposalComment', '');
            $motion->votingStatus    = \Yii::$app->request->post('votingStatus', '');
            if (\Yii::$app->request->post('proposalExplanation', null) !== null) {
                if (trim(\Yii::$app->request->post('proposalExplanation', '') === '')) {
                    $motion->proposalExplanation = null;
                } else {
                    $motion->proposalExplanation = \Yii::$app->request->post('proposalExplanation', '');
                }
            } else {
                $motion->proposalExplanation = null;
            }
            if (\Yii::$app->request->post('visible', 0)) {
                $motion->setProposalPublished();
            } else {
                $motion->proposalVisibleFrom = null;
            }
            $votingBlockId         = \Yii::$app->request->post('votingBlockId', null);
            $motion->votingBlockId = null;
            if ($votingBlockId === 'NEW') {
                $title = trim(\Yii::$app->request->post('votingBlockTitle', ''));
                if ($title !== '') {
                    $votingBlock                 = new VotingBlock();
                    $votingBlock->consultationId = $this->consultation->id;
                    $votingBlock->title          = $title;
                    $votingBlock->votingStatus   = IMotion::STATUS_VOTE;
                    $votingBlock->save();

                    $motion->votingBlockId = $votingBlock->id;
                }
            } elseif ($votingBlockId > 0) {
                $votingBlock = $this->consultation->getVotingBlock($votingBlockId);
                if ($votingBlock) {
                    $motion->votingBlockId = $votingBlock->id;
                }
            }

            $response['success'] = false;
            if ($motion->save()) {
                $response['success'] = true;
            }

            $this->consultation->refresh();
            $response['html']        = $this->renderPartial('_set_proposed_procedure', [
                'motion'   => $motion,
                'msgAlert' => $msgAlert,
            ]);
            $response['proposalStr'] = $motion->getFormattedProposalStatus(true);
        }

        if (\Yii::$app->request->post('notifyProposer') || \Yii::$app->request->post('sendAgain')) {
            try {
                new MotionProposedProcedure(
                    $motion,
                    \Yii::$app->request->post('text'),
                    \Yii::$app->request->post('fromName'),
                    \Yii::$app->request->post('replyTo')
                );
                $motion->proposalNotification = date('Y-m-d H:i:s');
                $motion->save();
                $response['success'] = true;
                $response['html']    = $this->renderPartial('_set_proposed_procedure', [
                    'motion'   => $motion,
                    'msgAlert' => $msgAlert,
                    'context'  => \Yii::$app->request->post('context', 'view'),
                ]);
            } catch (MailNotSent $e) {
                $response['success'] = false;
                $response['error']   = 'The mail could not be sent: ' . $e->getMessage();
            }
        }

        if (\Yii::$app->request->post('setProposerHasAccepted')) {
            $motion->proposalUserStatus = Motion::STATUS_ACCEPTED;
            $motion->save();
            ConsultationLog::log(
                $motion->getMyConsultation(),
                User::getCurrentUser()->id,
                ConsultationLog::MOTION_ACCEPT_PROPOSAL,
                $motion->id
            );
            $response['success'] = true;
            $response['html']    = $this->renderPartial('_set_proposed_procedure', [
                'motion'   => $motion,
                'msgAlert' => $msgAlert,
            ]);
        }

        if (\Yii::$app->request->post('writeComment')) {
            $adminComment               = new MotionAdminComment();
            $adminComment->userId       = User::getCurrentUser()->id;
            $adminComment->text         = \Yii::$app->request->post('writeComment');
            $adminComment->status       = MotionAdminComment::PROPOSED_PROCEDURE;
            $adminComment->dateCreation = date('Y-m-d H:i:s');
            $adminComment->motionId     = $motion->id;
            if (!$adminComment->save()) {
                \Yii::$app->response->statusCode = 500;
                $response['success']             = false;
                return json_encode($response);
            }

            $response['success'] = true;
            $response['comment'] = [
                'username'      => $adminComment->getMyUser()->name,
                'id'            => $adminComment->id,
                'text'          => $adminComment->text,
                'delLink'       => UrlHelper::createMotionUrl($motion, 'del-proposal-comment'),
                'dateFormatted' => Tools::formatMysqlDateTime($adminComment->dateCreation),
            ];
        }

        return json_encode($response);
    }

    /**
     * @param string $prefix
     * @return null|string
     */
    protected function guessRedirectByPrefix($prefix)
    {
        $motion = Motion::findOne([
            'consultationId' => $this->consultation->id,
            'titlePrefix'    => $prefix
        ]);
        if ($motion && $motion->isReadable()) {
            return $motion->getLink();
        }

        /** @var Amendment|null $amendment */
        $amendment = Amendment::find()->joinWith('motionJoin')->where([
            'motion.consultationId' => $this->consultation->id,
            'amendment.titlePrefix' => $prefix,
        ])->one();

        if ($amendment && $amendment->isReadable()) {
            return $amendment->getLink();
        }

        return null;
    }

    /**
     * URL: /[consultationPrefix]/[motionPrefix]
     *
     * @param string $prefix
     * @return \yii\console\Response|Response
     * @throws NotFoundHttpException
     */
    public function actionGotoPrefix($prefix)
    {
        $redirect = $this->guessRedirectByPrefix($prefix);
        if ($redirect) {
            return \Yii::$app->response->redirect($redirect);
        }
        throw new NotFoundHttpException();
    }
}
