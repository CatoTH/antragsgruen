<?php

namespace app\controllers;

use app\components\{HTMLTools, Tools, UrlHelper, EmailNotifications};
use app\models\db\{Amendment, AmendmentAdminComment, AmendmentSupporter, ConsultationLog, IMotion, User, VotingBlock};
use app\models\events\AmendmentEvent;
use app\models\exceptions\{FormError, MailNotSent, NotFound};
use app\models\forms\{AmendmentEditForm, AmendmentProposedChangeForm};
use app\models\notifications\AmendmentProposedProcedure;
use app\models\sectionTypes\ISectionType;
use app\views\amendment\LayoutHelper;
use yii\helpers\Html;
use yii\web\{NotFoundHttpException, Response};

/**
 * Class AmendmentController
 * @package app\controllers
 */
class AmendmentController extends Base
{
    use AmendmentActionsTrait;
    use AmendmentMergingTrait;

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionPdf($motionSlug, $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            return '';
        }

        if (!$amendment->isReadable()) {
            return $this->render('view_not_visible', ['amendment' => $amendment, 'adminEdit' => false]);
        }

        $filename                    = $amendment->getFilenameBase(false) . '.pdf';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');
        \yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        if ($hasLaTeX && $amendment->getMyMotionType()->texTemplateId) {
            return LayoutHelper::createPdfLatex($amendment);
        } else {
            return LayoutHelper::createPdfTcpdf($amendment);
        }
    }

    /**
     * @param int $withdrawn
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionPdfcollection($withdrawn = 0)
    {
        $withdrawn = ($withdrawn == 1);
        $motions   = $this->consultation->getVisibleMotionsSorted($withdrawn);
        if (count($motions) == 0) {
            $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
        }
        $amendments  = [];
        $texTemplate = null;
        foreach ($motions as $motion) {
            // If we have multiple motion types, we just take the template from the first one.
            if ($texTemplate === null) {
                $texTemplate = $motion->motionType->texTemplate;
            }
            $amendments = array_merge($amendments, $motion->getVisibleAmendmentsSorted($withdrawn));
        }
        if (count($amendments) == 0) {
            $this->showErrorpage(404, \Yii::t('amend', 'none_yet'));
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        if ($hasLaTeX && $texTemplate) {
            return $this->renderPartial('pdf_collection_tex', [
                'amendments'  => $amendments,
                'texTemplate' => $texTemplate,
            ]);
        } else {
            return $this->renderPartial('pdf_collection_tcpdf', ['amendments' => $amendments]);
        }
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionOdt($motionSlug, $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            return '';
        }

        if (!$amendment->isReadable()) {
            return $this->render('view_not_visible', ['amendment' => $amendment, 'adminEdit' => false]);
        }

        $filename                    = $amendment->getFilenameBase(false) . '.odt';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.text');
        \yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $this->renderPartial('view_odt', ['amendment' => $amendment]);
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     *
     * @return string
     */
    public function actionRest($motionSlug, $amendmentId)
    {
        $this->handleRestHeaders();

        try {
            $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId, null, true);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        if (!$amendment->isReadable()) {
            return $this->returnRestResponseFromException(new NotFound('Amendment is not readable'));
        }

        return $this->returnRestResponse(200, $this->renderPartial('rest_get', ['amendment' => $amendment]));
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @param int $commentId
     * @param string|null $procedureToken
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionView($motionSlug, $amendmentId, $commentId = 0, ?string $procedureToken = null)
    {
        $this->layout = 'column2';

        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId, 'view');
        if (!$amendment) {
            return '';
        }

        if (User::havePrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            $adminEdit = UrlHelper::createUrl(['admin/amendment/update', 'amendmentId' => $amendmentId]);
        } else {
            $adminEdit = null;
        }

        if (!$amendment->isReadable()) {
            return $this->render('view_not_visible', ['amendment' => $amendment, 'adminEdit' => $adminEdit]);
        }

        $openedComments      = [];
        $amendmentViewParams = [
            'amendment'      => $amendment,
            'openedComments' => $openedComments,
            'adminEdit'      => $adminEdit,
            'commentForm'    => null,
            'procedureToken' => $procedureToken,
        ];

        try {
            $this->performShowActions($amendment, $commentId, $amendmentViewParams);
        } catch (\Exception $e) {
            \yii::$app->session->setFlash('error', $e->getMessage());
        }

        $supportStatus = '';
        if (!\Yii::$app->user->isGuest) {
            foreach ($amendment->amendmentSupporters as $supp) {
                if ($supp->userId == User::getCurrentUser()->id) {
                    $supportStatus = $supp->role;
                }
            }
        }
        $amendmentViewParams['supportStatus'] = $supportStatus;


        return $this->render('view', $amendmentViewParams);
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @return string
     */
    public function actionAjaxDiff($motionSlug, $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            return '';
        }

        return $this->renderPartial('ajax_diff', ['amendment' => $amendment]);
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @param string $fromMode
     * @return string
     */
    public function actionCreatedone($motionSlug, $amendmentId, $fromMode)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        /** @var Amendment $amendment */
        $amendment = Amendment::findOne(
            [
                'id'       => $amendmentId,
                'motionId' => $motion->id
            ]
        );
        return $this->render('create_done', ['amendment' => $amendment, 'mode' => $fromMode]);
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @param string $fromMode
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionCreateconfirm($motionSlug, $amendmentId, $fromMode)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        /** @var Amendment $amendment */
        $amendment = Amendment::findOne(
            [
                'id'       => $amendmentId,
                'motionId' => $motion->id,
                'status'   => Amendment::STATUS_DRAFT
            ]
        );
        if (!$amendment) {
            \Yii::$app->session->setFlash('error', \Yii::t('amend', 'err_not_found'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if ($this->isPostSet('modify')) {
            $nextUrl = ['amendment/edit', 'amendmentId' => $amendment->id, 'motionSlug' => $motionSlug];
            return $this->redirect(UrlHelper::createUrl($nextUrl));
        }

        if ($this->isPostSet('confirm')) {
            $amendment->trigger(Amendment::EVENT_SUBMITTED, new AmendmentEvent($amendment));

            if ($amendment->status === Amendment::STATUS_SUBMITTED_SCREENED) {
                $amendment->trigger(Amendment::EVENT_PUBLISHED, new AmendmentEvent($amendment));
            } else {
                EmailNotifications::sendAmendmentSubmissionConfirm($amendment);
            }

            return $this->redirect(UrlHelper::createAmendmentUrl($amendment, 'createdone', ['fromMode' => $fromMode]));
        } else {
            return $this->render('create_confirm', [
                'amendment'     => $amendment,
                'mode'          => $fromMode,
                'deleteDraftId' => \Yii::$app->request->get('draftId'),
            ]);
        }
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @return string
     */
    public function actionEdit($motionSlug, $amendmentId)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        /** @var Amendment $amendment */
        $amendment = Amendment::findOne(
            [
                'id'       => $amendmentId,
                'motionId' => $motion->id,
            ]
        );
        if (!$amendment) {
            \Yii::$app->session->setFlash('error', \Yii::t('amend', 'err_not_found'));
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (!$amendment->canEdit()) {
            \Yii::$app->session->setFlash('error', \Yii::t('amend', 'err_edit_forbidden'));
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        $fromMode = ($amendment->status == Amendment::STATUS_DRAFT ? 'create' : 'edit');
        $form     = new AmendmentEditForm($amendment->getMyMotion(), $amendment);

        if ($this->isPostSet('save')) {
            $amendment->flushCacheWithChildren(null);
            $form->setAttributes([\Yii::$app->request->post(), $_FILES]);
            try {
                $form->saveAmendment($amendment);

                if ($amendment->isVisible()) {
                    ConsultationLog::logCurrUser($this->consultation, ConsultationLog::AMENDMENT_CHANGE, $amendment->id);
                }

                if ($amendment->status == Amendment::STATUS_DRAFT) {
                    $nextUrl = [
                        'amendment/createconfirm',
                        'motionSlug'  => $motionSlug,
                        'amendmentId' => $amendment->id,
                        'fromMode'    => $fromMode,
                        'draftId'     => $this->getRequestValue('draftId'),
                    ];
                    return $this->redirect(UrlHelper::createUrl($nextUrl));
                } else {
                    return $this->render('edit_done', ['amendment' => $amendment]);
                }
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
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
     * @param string $motionSlug
     * @param int $cloneFrom
     * @return string
     * @throws \app\models\exceptions\Internal
     * @throws \app\models\exceptions\NotAmendable
     * @throws \yii\base\ExitException
     */
    public function actionCreate($motionSlug, $cloneFrom = 0)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (!$motion->isCurrentlyAmendable()) {
            if ($motion->isCurrentlyAmendable(true, true)) {
                $loginUrl = UrlHelper::createLoginUrl(['amendment/create', 'motionSlug' => $motion->getMotionSlug()]);
                return $this->redirect($loginUrl);
            } else {
                return $this->showErrorpage(403, \Yii::t('amend', 'err_create_permission'));
            }
        }

        $form        = new AmendmentEditForm($motion, null);
        $supportType = $motion->getMyMotionType()->getAmendmentSupportTypeClass();
        $iAmAdmin    = User::havePrivilege($this->consultation, User::PRIVILEGE_SCREENING);

        if ($this->isPostSet('save')) {
            try {
                $amendment = $form->createAmendment();

                // Supporting members are not collected in the form, but need to be copied a well
                if ($supportType->collectSupportersBeforePublication() && $cloneFrom && $iAmAdmin) {
                    $adoptAmend = $this->consultation->getAmendment($cloneFrom);
                    foreach ($adoptAmend->getSupporters() as $supp) {
                        $suppNew = new AmendmentSupporter();
                        $suppNew->setAttributes($supp->getAttributes());
                        $suppNew->id           = null;
                        $suppNew->amendmentId  = $amendment->id;
                        $suppNew->extraData    = $supp->extraData;
                        $suppNew->dateCreation = date('Y-m-d H:i:s');
                        $suppNew->save();
                    }
                }

                $nextUrl = [
                    'amendment/createconfirm',
                    'motionSlug'  => $motionSlug,
                    'amendmentId' => $amendment->id,
                    'fromMode'    => 'create',
                    'draftId'     => $this->getRequestValue('draftId'),
                ];
                return $this->redirect(UrlHelper::createUrl($nextUrl));
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }
        } elseif ($cloneFrom > 0) {
            $adoptAmend = $this->consultation->getAmendment($cloneFrom);
            $form->cloneSupporters($adoptAmend);
            $form->cloneAmendmentText($adoptAmend);
        }

        if (count($form->supporters) == 0) {
            $supporter               = new AmendmentSupporter();
            $supporter->role         = AmendmentSupporter::ROLE_INITIATOR;
            $supporter->dateCreation = date('Y-m-d H:i:s');
            if (User::getCurrentUser() && !$iAmAdmin) {
                $user                    = User::getCurrentUser();
                $supporter->userId       = $user->id;
                $supporter->name         = $user->name;
                $supporter->contactEmail = $user->email;
                $supporter->personType   = AmendmentSupporter::PERSON_NATURAL;
            }
            $form->supporters[] = $supporter;
        }

        return $this->render(
            'edit_form',
            [
                'mode'         => 'create',
                'consultation' => $this->consultation,
                'form'         => $form,
            ]
        );
    }

    /**
     * @param int $amendmentId
     * @return string
     */
    public function actionWithdraw($amendmentId)
    {
        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment) {
            \Yii::$app->session->setFlash('error', \Yii::t('amend', 'err_not_found'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (!$amendment->canWithdraw()) {
            \Yii::$app->session->setFlash('error', \Yii::t('amend', 'err_withdraw_forbidden'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if ($this->isPostSet('cancel')) {
            return $this->redirect(UrlHelper::createAmendmentUrl($amendment));
        }

        if ($this->isPostSet('withdraw')) {
            $amendment->withdraw();
            \Yii::$app->session->setFlash('success', \Yii::t('amend', 'widthdraw_done'));
            return $this->redirect(UrlHelper::createAmendmentUrl($amendment));
        }

        return $this->render('withdraw', ['amendment' => $amendment]);
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @return string
     * @throws \app\models\exceptions\Internal
     * @throws \yii\base\ExitException
     */
    public function actionSaveProposalStatus($motionSlug, $amendmentId)
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            \Yii::$app->response->statusCode = 404;
            return 'Amendment not found';
        }
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CHANGE_PROPOSALS)) {
            \Yii::$app->response->statusCode = 403;
            return 'Not permitted to change the status';
        }

        $response = [];
        $msgAlert = null;

        if (\Yii::$app->request->post('setStatus', null) !== null) {
            $setStatus = IntVal(\Yii::$app->request->post('setStatus'));
            if ($amendment->proposalStatus !== $setStatus) {
                if ($amendment->proposalUserStatus !== null) {
                    $msgAlert = \Yii::t('amend', 'proposal_user_change_reset');
                }
                $amendment->proposalUserStatus = null;
            }
            $amendment->proposalStatus  = $setStatus;
            $amendment->proposalComment = \Yii::$app->request->post('proposalComment', '');
            $amendment->votingStatus    = \Yii::$app->request->post('votingStatus', '');
            if (\Yii::$app->request->post('proposalExplanation', null) !== null) {
                if (trim(\Yii::$app->request->post('proposalExplanation', '') === '')) {
                    $amendment->proposalExplanation = null;
                } else {
                    $amendment->proposalExplanation = \Yii::$app->request->post('proposalExplanation', '');
                }
            } else {
                $amendment->proposalExplanation = null;
            }
            if (\Yii::$app->request->post('visible', 0)) {
                $amendment->setProposalPublished();
            } else {
                $amendment->proposalVisibleFrom = null;
            }
            $votingBlockId            = \Yii::$app->request->post('votingBlockId', null);
            $amendment->votingBlockId = null;
            if ($votingBlockId === 'NEW') {
                $title = trim(\Yii::$app->request->post('votingBlockTitle', ''));
                if ($title !== '') {
                    $votingBlock                 = new VotingBlock();
                    $votingBlock->consultationId = $this->consultation->id;
                    $votingBlock->title          = $title;
                    $votingBlock->votingStatus   = IMotion::STATUS_VOTE;
                    $votingBlock->save();

                    $amendment->votingBlockId = $votingBlock->id;
                }
            } elseif ($votingBlockId > 0) {
                $votingBlock = $this->consultation->getVotingBlock($votingBlockId);
                if ($votingBlock) {
                    $amendment->votingBlockId = $votingBlock->id;
                }
            }

            $response['success'] = false;
            if ($amendment->save()) {
                $response['success'] = true;
            }
            $amendment->flushCacheItems(['procedure']);

            $this->consultation->refresh();
            $response['html']        = $this->renderPartial('_set_proposed_procedure', [
                'amendment' => $amendment,
                'msgAlert'  => $msgAlert,
                'context'   => \Yii::$app->request->post('context', 'view'),
            ]);
            $response['proposalStr'] = $amendment->getFormattedProposalStatus(true);
        }

        if (\Yii::$app->request->post('notifyProposer') || \Yii::$app->request->post('sendAgain')) {
            try {
                new AmendmentProposedProcedure(
                    $amendment,
                    \Yii::$app->request->post('text'),
                    \Yii::$app->request->post('fromName'),
                    \Yii::$app->request->post('replyTo')
                );
                $amendment->proposalNotification = date('Y-m-d H:i:s');
                $amendment->save();
                $amendment->flushCacheItems(['procedure']);
                $response['success'] = true;
                $response['html']    = $this->renderPartial('_set_proposed_procedure', [
                    'amendment' => $amendment,
                    'msgAlert'  => $msgAlert,
                    'context'   => \Yii::$app->request->post('context', 'view'),
                ]);
            } catch (MailNotSent $e) {
                $response['success'] = false;
                $response['error']   = 'The mail could not be sent: ' . $e->getMessage();
            }
        }

        if (\Yii::$app->request->post('setProposerHasAccepted')) {
            $amendment->proposalUserStatus = Amendment::STATUS_ACCEPTED;
            $amendment->save();
            $amendment->flushCacheItems(['procedure']);
            ConsultationLog::log(
                $amendment->getMyConsultation(),
                User::getCurrentUser()->id,
                ConsultationLog::AMENDMENT_ACCEPT_PROPOSAL,
                $amendment->id
            );
            $response['success'] = true;
            $response['html']        = $this->renderPartial('_set_proposed_procedure', [
                'amendment' => $amendment,
                'msgAlert'  => $msgAlert,
                'context'   => \Yii::$app->request->post('context', 'view'),
            ]);
        }

        if (\Yii::$app->request->post('writeComment')) {
            $adminComment               = new AmendmentAdminComment();
            $adminComment->userId       = User::getCurrentUser()->id;
            $adminComment->text         = \Yii::$app->request->post('writeComment');
            $adminComment->status       = AmendmentAdminComment::PROPOSED_PROCEDURE;
            $adminComment->dateCreation = date('Y-m-d H:i:s');
            $adminComment->amendmentId  = $amendment->id;
            if (!$adminComment->save()) {
                \Yii::$app->response->statusCode = 500;
                $response['success']             = false;
                return json_encode($response);
            }
            $amendment->flushCacheItems(['procedure']);

            $response['success'] = true;
            $response['comment'] = [
                'username'      => $adminComment->getMyUser()->name,
                'id'            => $adminComment->id,
                'text'          => $adminComment->text,
                'delLink'       => UrlHelper::createAmendmentUrl($amendment, 'del-proposal-comment'),
                'dateFormatted' => Tools::formatMysqlDateTime($adminComment->dateCreation),
            ];
        }

        return json_encode($response);
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @return string
     * @throws \app\models\exceptions\Internal
     * @throws \yii\base\ExitException
     */
    public function actionEditProposedChange($motionSlug, $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            \Yii::$app->response->statusCode = 404;
            return 'Amendment not found';
        }
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CHANGE_PROPOSALS)) {
            \Yii::$app->response->statusCode = 403;
            return 'Not permitted to change the status';
        }


        if (\Yii::$app->request->post('reset', null) !== null) {
            $reference = $amendment->getMyProposalReference();
            if ($reference && $reference->status === Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT) {
                foreach ($reference->sections as $section) {
                    $section->delete();
                }

                $amendment->proposalReferenceId = null;
                $amendment->save();

                $reference->delete();
            }
            $amendment->flushCacheItems(['procedure']);
        }

        $form = new AmendmentProposedChangeForm($amendment);

        $msgSuccess = null;
        $msgAlert   = null;

        if (\Yii::$app->request->post('save', null) !== null) {
            $form->save(\Yii::$app->request->post(), $_FILES);
            $msgSuccess = \Yii::t('base', 'saved');

            if ($amendment->proposalUserStatus !== null) {
                $msgAlert = \Yii::t('amend', 'proposal_user_change_reset');
            }
            $amendment->proposalUserStatus = null;
            $amendment->save();
            $amendment->flushCacheItems(['procedure']);
        }

        return $this->render('edit_proposed_change', [
            'msgSuccess' => $msgSuccess,
            'msgAlert'   => $msgAlert,
            'amendment'  => $amendment,
            'form'       => $form,
        ]);
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @return string
     * @throws \app\models\exceptions\Internal
     * @throws \yii\base\ExitException
     */
    public function actionEditProposedChangeCheck($motionSlug, $amendmentId)
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            \Yii::$app->response->statusCode = 404;
            return 'Amendment not found';
        }
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CHANGE_PROPOSALS)) {
            \Yii::$app->response->statusCode = 403;
            return 'Not permitted to change the status';
        }

        $newSections = \Yii::$app->request->post('sections', []);
        foreach ($newSections as $sectionId => $section) {
            $newSections[$sectionId] = HTMLTools::cleanSimpleHtml($section);
        }

        /** @var Amendment[] $collidesWith */
        $collidesWith = [];
        foreach ($amendment->getMyMotion()->getAmendmentsProposedToBeIncluded(true, [$amendment->id]) as $compAmend) {
            foreach ($compAmend->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                $coll = $section->getRewriteCollisions($newSections[$section->sectionId], false);
                if (count($coll) > 0 && !in_array($compAmend, $collidesWith, true)) {
                    $collidesWith[] = $compAmend;
                }
            }
        }

        return json_encode([
            'collisions' => array_map(function (Amendment $amend) {
                // Keep in sync with edit_proposed_change.php
                $title = $amend->getShortTitle();
                if ($amend->proposalStatus == Amendment::STATUS_VOTE) {
                    $title .= ' (' . \Yii::t('amend', 'proposal_voting') . ')';
                }
                $html = '<li>' . Html::a($title, UrlHelper::createAmendmentUrl($amend), ['target' => '_blank']);
                $html .= HTMLTools::amendmentDiffTooltip($amend, 'top', 'fixedBottom');
                $html .= '</li>';

                return [
                    'id'    => $amend->id,
                    'title' => $amend->getShortTitle(),
                    'html'  => $html,
                ];
            }, $collidesWith),
        ]);
    }

    /**
     * URL: /[consultationPrefix]/[motionPrefix]/[amendmentPrefix]
     *
     * @param string $prefix1
     * @param string $prefix2
     * @return \yii\console\Response|Response
     * @throws NotFoundHttpException
     */
    public function actionGotoPrefix($prefix1, $prefix2)
    {
        try {
            /** @var Amendment|null $amendment */
            $amendment = Amendment::find()->joinWith('motionJoin')->where([
                'motion.consultationId' => $this->consultation->id,
                'motion.titlePrefix'    => $prefix1,
                'amendment.titlePrefix' => $prefix2,
            ])->one();

            if ($amendment && $amendment->isReadable()) {
                return \Yii::$app->response->redirect($amendment->getLink());
            }
        } catch (\Exception $e) {
            throw new NotFoundHttpException();
        }
        throw new NotFoundHttpException();
    }
}
