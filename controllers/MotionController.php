<?php

namespace app\controllers;

use app\components\{UrlHelper, EmailNotifications};
use app\models\db\{ConsultationAgendaItem,
    ConsultationLog,
    ConsultationMotionType,
    ConsultationSettingsMotionSection,
    Motion,
    MotionSupporter,
    User,
    UserNotification
};
use app\models\exceptions\{ExceptionBase, FormError, Inconsistency, Internal};
use app\models\forms\MotionEditForm;
use app\models\sectionTypes\ISectionType;
use app\models\MotionSectionChanges;
use app\models\events\MotionEvent;
use yii\web\{NotFoundHttpException, Response};

class MotionController extends Base
{
    use MotionActionsTrait;
    use MotionMergingTrait;
    use MotionExportTraits;

    /**
     * @param string $motionSlug
     * @param int $commentId
     * @param string|null $procedureToken
     *
     * @return string
     */
    public function actionView($motionSlug, $commentId = 0, ?string $procedureToken = null)
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
     *
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
     * @param string $fromMode
     *
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
     *
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
     *
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
     *
     * @return array
     * @throws Internal
     * @throws \app\models\exceptions\NotFound
     */
    private function getMotionTypeForCreate($motionTypeId = 0, $agendaItemId = 0, $cloneFrom = 0)
    {
        $motionTypeId = intval($motionTypeId);

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
     *
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
     *
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
     * URL: /[consultationPrefix]/[motionPrefix]
     *
     * @param string $prefix
     *
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
