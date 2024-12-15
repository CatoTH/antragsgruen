<?php

namespace app\controllers;

use app\components\RequestContext;
use app\components\UrlHelper;
use app\models\db\{Amendment,
    ConsultationAgendaItem,
    ConsultationLog,
    ConsultationMotionType,
    ConsultationSettingsMotionSection,
    ISupporter,
    Motion,
    MotionSupporter,
    SpeechQueue,
    User,
    UserNotification};
use app\models\http\{HtmlErrorResponse, HtmlResponse, RedirectResponse, ResponseInterface};
use app\models\settings\Privileges;
use app\models\exceptions\{ExceptionBase, FormError, Inconsistency, Internal, ResponseException};
use app\models\forms\MotionEditForm;
use app\models\sectionTypes\ISectionType;
use app\models\MotionSectionChanges;
use app\models\events\MotionEvent;
use yii\web\NotFoundHttpException;

class MotionController extends Base
{
    use MotionActionsTrait;
    use MotionMergingTrait;
    use MotionExportTraits;

    public const VIEW_ID_VIEW = 'view';
    public const VIEW_ID_VIEW_CHANGES = 'view-changes';
    public const VIEW_ID_VIEW_PDF = 'pdf';

    public const VIEW_ID_MERGING_STATUS_AJAX = 'merge-amendments-status-ajax';
    public const VIEW_ID_MERGING_PUBLIC_AJAX = 'merge-amendments-public-ajax';

    public function actionView(string $motionSlug, int $commentId = 0, ?string $procedureToken = null): HtmlResponse
    {
        $this->layout = 'column2';

        $motion = $this->getMotionWithCheck($motionSlug);
        $this->motion = $motion;
        if ($this->consultation->havePrivilege(Privileges::PRIVILEGE_SCREENING, null)) {
            $adminEdit = UrlHelper::createUrl(['admin/motion/update', 'motionId' => $motion->id]);
        } else {
            $adminEdit = null;
        }

        if (!$motion->isReadable()) {
            return new HtmlResponse($this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => $adminEdit]));
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

        $textSections = $motion->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE);
        if (count($textSections) === 0) {
            $commentWholeMotions = true;
        } else {
            $commentWholeMotions = false;
            foreach ($textSections as $section) {
                if ($section->getSettings()->hasComments === ConsultationSettingsMotionSection::COMMENTS_MOTION) {
                    $commentWholeMotions = true;
                }
            }
        }

        $motionViewParams = [
            'motion'              => $motion,
            'openedComments'      => $openedComments,
            'adminEdit'           => $adminEdit,
            'commentForm'         => null,
            'commentWholeMotions' => $commentWholeMotions,
            'procedureToken'      => $procedureToken,
        ];

        try {
            $this->performShowActions($motion, intval($commentId), $motionViewParams);
        } catch (ResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->getHttpSession()->setFlash('error', $e->getMessage());
        }

        $motionViewParams['supportStatus'] = MotionSupporter::getCurrUserSupportStatus($motion);

        return new HtmlResponse($this->render('view', $motionViewParams));
    }

    public function actionViewChanges(string $motionSlug): ResponseInterface
    {
        $this->layout = 'column2';

        $motion = $this->getMotionWithCheck($motionSlug);
        $parentMotion = $motion->replacedMotion;

        if (!$motion->isReadable()) {
            return new HtmlResponse($this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]));
        }
        if (!$parentMotion || !$parentMotion->isReadable()) {
            $this->getHttpSession()->setFlash('error', 'The diff-view is not available');

            return new RedirectResponse(UrlHelper::createMotionUrl($motion));
        }

        try {
            $changes = MotionSectionChanges::motionToSectionChanges($parentMotion, $motion);
        } catch (Inconsistency $e) {
            $changes = [];
            $this->getHttpSession()->setFlash('error', $e->getMessage());
        }

        return new HtmlResponse($this->render('view_changes', [
            'newMotion' => $motion,
            'oldMotion' => $parentMotion,
            'changes'   => $changes,
        ]));
    }

    public function actionCreateSelectStatutes(?string $motionTypeId = null, ?string $agendaItemId = null): ResponseInterface
    {
        $agendaItem = null;
        $motionType = null;

        if ($motionTypeId) {
            $motionType = $this->consultation->getMotionType(intval($motionTypeId));
        } elseif ($agendaItemId) {
            $agendaItem = $this->consultation->getAgendaItem(intval($agendaItemId));
            if ($agendaItem && $agendaItem->getMyMotionType()) {
                $motionType = $agendaItem->getMyMotionType();
            }
        }
        if (!$motionType) {
            return new HtmlErrorResponse(400, 'No motion type found');
        }
        return new HtmlResponse($this->render('create_select_statutes', ['motionType' => $motionType, 'agendaItem' => $agendaItem]));
    }

    public function actionCreatedone(string $motionSlug, string $fromMode): string
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_not_found'));

            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if ($motion->getMyMotionType()->amendmentsOnly) {
            return $this->render('create_done_amendments_only', ['motion' => $motion, 'mode' => $fromMode]);
        } else {
            return $this->render('create_done', ['motion' => $motion, 'mode' => $fromMode]);
        }
    }

    public function actionCreateconfirm(string $motionSlug, string $fromMode): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion || $motion->status !== Motion::STATUS_DRAFT) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_not_found'));

            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }
        if (!$motion->canEditText()) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_edit_permission'));

            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        if ($this->isPostSet('modify')) {
            return new RedirectResponse(UrlHelper::createMotionUrl($motion, 'edit'));
        }

        if ($this->isPostSet('confirm')) {
            $motion->trigger(Motion::EVENT_SUBMITTED, new MotionEvent($motion));

            if ($motion->status === Motion::STATUS_SUBMITTED_SCREENED) {
                $motion->trigger(Motion::EVENT_PUBLISHED, new MotionEvent($motion));
            }

            if (User::getCurrentUser()) {
                UserNotification::addNotification(
                    User::getCurrentUser(),
                    $this->consultation,
                    UserNotification::NOTIFICATION_AMENDMENT_MY_MOTION
                );
            }

            return new RedirectResponse(UrlHelper::createMotionUrl($motion, 'createdone', ['fromMode' => $fromMode]));
        } else {
            $params                  = ['motion' => $motion, 'mode' => $fromMode];
            $params['deleteDraftId'] = $this->getRequestValue('draftId');

            return new HtmlResponse($this->render('create_confirm', $params));
        }
    }

    public function actionEdit(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_not_found'));

            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        if (!$motion->canEditText()) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_edit_permission'));

            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        $form = new MotionEditForm($motion->motionType, $motion->agendaItem, $motion);
        if (!$motion->canEditInitiators()) {
            $form->setAllowEditingInitiators(false);
        }
        $fromMode = ($motion->status === Motion::STATUS_DRAFT ? 'create' : 'edit');

        if ($this->isPostSet('save')) {
            $post = $this->getHttpRequest()->post();
            $motion->flushCache(true);
            $form->setAttributes($post, $_FILES);
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

                    return new RedirectResponse($nextUrl);
                } else {
                    return new HtmlResponse($this->render('edit_done', ['motion' => $motion]));
                }
            } catch (FormError $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
                $form->setSectionTextWithoutSaving($motion, $post['sections']);
            }
        }

        return new HtmlResponse($this->render(
            'edit_form',
            [
                'mode'         => $fromMode,
                'form'         => $form,
                'consultation' => $this->consultation,
            ]
        ));
    }

    /**
     * @throws Internal
     * @throws \app\models\exceptions\NotFound
     */
    private function getMotionTypeForCreate(int $motionTypeId = 0, int $agendaItemId = 0, int $cloneFrom = 0): array
    {
        if ($agendaItemId > 0) {
            $agendaItem = $this->consultation->getAgendaItem($agendaItemId);
            if (!$agendaItem) {
                throw new Internal('Could not find agenda item');
            }
            if (!$agendaItem->getMyMotionType()) {
                throw new Internal('Agenda item does not have motions');
            }
            $motionType = $agendaItem->getMyMotionType();
        } elseif ($motionTypeId > 0) {
            $motionType = $this->consultation->getMotionType($motionTypeId);
            $agendaItem = null;
        } elseif ($cloneFrom > 0) {
            $motion = $this->consultation->getMotion($cloneFrom);
            if (!$motion) {
                throw new Internal('Could not find referenced motion');
            }
            $motionType = $motion->getMyMotionType();
            $agendaItem = $motion->agendaItem;
        } else {
            throw new Internal('Could not resolve motion type');
        }

        return [$motionType, $agendaItem];
    }


    public function actionCreate(int $motionTypeId = 0, int $agendaItemId = 0, int $cloneFrom = 0): ResponseInterface
    {
        try {
            $ret = $this->getMotionTypeForCreate(intval($motionTypeId), intval($agendaItemId), intval($cloneFrom));
            list($motionType, $agendaItem) = $ret;
        } catch (ExceptionBase $e) {
            $this->getHttpSession()->setFlash('error', $e->getMessage());

            return new RedirectResponse(UrlHelper::homeUrl());
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

                return new RedirectResponse($loginUrl);
            } else {
                return new HtmlErrorResponse(403, \Yii::t('motion', 'err_create_permission'));
            }
        }

        $form = new MotionEditForm($motionType, $agendaItem, null);
        $supportType = $motionType->getMotionSupportTypeClass();
        $iAmAdmin = $this->consultation->havePrivilege(Privileges::PRIVILEGE_SCREENING, null);

        if ($this->isPostSet('save')) {
            try {
                $motion = $form->createMotion();

                // Supporting members are not collected in the form, but need to be copied a well
                if ($supportType->collectSupportersBeforePublication() && $cloneFrom && $iAmAdmin) {
                    $adoptMotion = $this->consultation->getMotion($cloneFrom);
                    foreach ($adoptMotion->getSupporters(true) as $supp) {
                        $suppNew = new MotionSupporter();
                        $suppNew->setAttributes($supp->getAttributes());
                        $suppNew->id           = null;
                        $suppNew->motionId     = $motion->id;
                        $suppNew->extraData    = $supp->extraData;
                        $suppNew->dateCreation = date('Y-m-d H:i:s');
                        if ($supp->isNonPublic()) {
                            $suppNew->setExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_NON_PUBLIC, true);
                        }
                        $suppNew->save();
                    }
                }

                return new RedirectResponse(UrlHelper::createMotionUrl($motion, 'createconfirm', [
                    'fromMode' => 'create',
                    'draftId'  => $this->getRequestValue('draftId'),
                ]));
            } catch (FormError $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }
        } elseif ($cloneFrom > 0) {
            $motion = $this->consultation->getMotion($cloneFrom);
            $form->cloneSupporters($motion);
            $form->cloneMotionText($motion);
        }


        if (count($form->supporters) === 0) {
            $form->supporters[] = MotionSupporter::createInitiator($this->consultation, $supportType, $iAmAdmin);
        }

        return new HtmlResponse($this->render(
            'edit_form',
            [
                'mode'         => 'create',
                'form'         => $form,
                'consultation' => $this->consultation,
            ]
        ));
    }


    public function actionWithdraw(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_not_found'));

            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        if (!$motion->canWithdraw()) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_withdraw_permission'));

            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        if ($this->isPostSet('cancel')) {
            return new RedirectResponse(UrlHelper::createMotionUrl($motion));
        }

        if ($this->isPostSet('withdraw')) {
            $motion->withdraw();
            $this->getHttpSession()->setFlash('success', \Yii::t('motion', 'withdraw_done'));

            return new RedirectResponse(UrlHelper::createMotionUrl($motion));
        }

        return new HtmlResponse($this->render('withdraw', ['motion' => $motion]));
    }

    public function actionAdminSpeech(string $motionSlug): ResponseInterface
    {
        $this->layout = 'column2';

        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_not_found'));

            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }
        $user = User::getCurrentUser();
        if (!$user->hasPrivilege($this->consultation, Privileges::PRIVILEGE_SPEECH_QUEUES, null)) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_edit_permission'));

            return new RedirectResponse(UrlHelper::createMotionUrl($motion));
        }

        if (count($motion->speechQueues) === 0) {
            $speechQueue = SpeechQueue::createWithSubqueues($this->consultation, false);
            $speechQueue->motionId = $motion->id;
            $speechQueue->save();
        } else {
            $speechQueue = $motion->speechQueues[0];
        }

        return new HtmlResponse($this->render('@app/views/speech/admin-singlepage', ['queue' => $speechQueue]));
    }

    protected function guessRedirectByPrefix(string $prefix): ?string
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
     * @throws NotFoundHttpException
     */
    public function actionGotoPrefix(string $prefix): RedirectResponse
    {
        $redirect = $this->guessRedirectByPrefix($prefix);
        if ($redirect) {
            return new RedirectResponse($redirect);
        }
        throw new NotFoundHttpException();
    }
}
