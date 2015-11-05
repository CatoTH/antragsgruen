<?php

namespace app\controllers;

use app\components\mail\Tools;
use app\components\MotionSorter;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentSupporter;
use app\models\db\ConsultationLog;
use app\models\db\EMailLog;
use app\models\db\Motion;
use app\models\db\User;
use app\models\exceptions\FormError;
use app\models\exceptions\MailNotSent;
use app\models\exceptions\NotFound;
use app\models\forms\AmendmentEditForm;
use yii\web\Response;

class AmendmentController extends Base
{
    use AmendmentActionsTrait;

    /**
     * @param int $motionId
     * @param int $amendmentId
     * @return Amendment|null
     */
    private function getAmendmentWithCheck($motionId, $amendmentId)
    {
        $motion    = $this->consultation->getMotion($motionId);
        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment || !$motion) {
            $this->redirect(UrlHelper::createUrl('consultation/index'));
            return null;
        }
        $this->checkConsistency($motion, $amendment);
        return $amendment;
    }

    /**
     * @param int $motionId
     * @param int $amendmentId
     * @return string
     */
    public function actionPdf($motionId, $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionId, $amendmentId);
        if (!$amendment) {
            return '';
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');

        if ($this->getParams()->xelatexPath) {
            return $this->renderPartial('pdf_tex', ['amendment' => $amendment]);
        } else {
            return $this->renderPartial('pdf_tcpdf', ['amendment' => $amendment]);
        }
    }

    /**
     * @return string
     */
    public function actionPdfcollection()
    {
        $motions = $this->consultation->getVisibleMotionsSorted();
        if (count($motions) == 0) {
            $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
        }
        $amendments = [];
        foreach ($motions as $motion) {
            $amendments = array_merge($amendments, $motion->getVisibleAmendmentsSorted());
        }
        if (count($amendments) == 0) {
            $this->showErrorpage(404, \Yii::t('amend', 'none_yet'));
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');
        if ($this->getParams()->xelatexPath) {
            return $this->renderPartial('pdf_collection_tex', ['amendments' => $amendments]);
        } else {
            return $this->renderPartial('pdf_collection_tcpdf', ['amendments' => $amendments]);
        }
    }

    /**
     * @param int $motionId
     * @param int $amendmentId
     * @return string
     */
    public function actionOdt($motionId, $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionId, $amendmentId);
        if (!$amendment) {
            return '';
        }

        $filename                    = 'Amendment_' . $amendment->titlePrefix . '.odt';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.text');
        \yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');

        return $this->renderPartial('odt', ['amendment' => $amendment]);
    }

    /**
     * @param int $motionId
     * @param int $amendmentId
     * @param int $commentId
     * @return string
     */
    public function actionView($motionId, $amendmentId, $commentId = 0)
    {
        $amendment = $this->getAmendmentWithCheck($motionId, $amendmentId);
        if (!$amendment) {
            return '';
        }

        $this->layout = 'column2';

        $openedComments = [];

        if (User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            $adminEdit = UrlHelper::createUrl(['admin/amendment/update', 'amendmentId' => $amendmentId]);
        } else {
            $adminEdit = null;
        }


        $amendmentViewParams = [
            'amendment'      => $amendment,
            'editLink'       => $amendment->canEdit(),
            'openedComments' => $openedComments,
            'adminEdit'      => $adminEdit,
            'commentForm'    => null,
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
     * @param int $motionId
     * @param int $amendmentId
     * @param string $fromMode
     * @return string
     */
    public function actionCreateconfirm($motionId, $amendmentId, $fromMode)
    {
        /** @var Amendment $amendment */
        $amendment = Amendment::findOne(
            [
                'id'       => $amendmentId,
                'motionId' => $motionId,
                'status'   => Amendment::STATUS_DRAFT
            ]
        );
        if (!$amendment) {
            \Yii::$app->session->setFlash('error', 'Amendment not found.');
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (isset($_POST['modify'])) {
            $nextUrl = ['amendment/edit', 'amendmentId' => $amendment->id, 'motionId' => $amendment->motionId];
            return $this->redirect(UrlHelper::createUrl($nextUrl));
        }

        if (isset($_POST['confirm'])) {
            $screening = $this->consultation->getSettings()->screeningAmendments;
            if ($screening) {
                $amendment->status = Amendment::STATUS_SUBMITTED_UNSCREENED;
            } else {
                $amendment->status = Amendment::STATUS_SUBMITTED_SCREENED;
                if ($amendment->titlePrefix == '') {
                    $numbering              = $amendment->motion->consultation->getAmendmentNumbering();
                    $amendment->titlePrefix = $numbering->getAmendmentNumber($amendment, $amendment->motion);
                }
            }
            $amendment->save();

            $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment));
            $mailText      = str_replace(
                ['%TITLE%', '%LINK%', '%INITIATOR%'],
                [$amendment->getTitle(), $amendmentLink, $amendment->getInitiatorsStr()],
                \Yii::t('amend', 'submitted_adminnoti_body')
            );
            $mailTitle     = \Yii::t('amend', 'submitted_adminnoti_title');
            $amendment->motion->consultation->sendEmailToAdmins($mailTitle, $mailText);

            if ($amendment->status == Amendment::STATUS_SUBMITTED_SCREENED) {
                $amendment->onPublish();
            } else {
                if ($amendment->motion->consultation->getSettings()->initiatorConfirmEmails) {
                    $initiator = $amendment->getInitiators();
                    if (count($initiator) > 0 && $initiator[0]->contactEmail != '') {
                        try {
                            Tools::sendWithLog(
                                EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                                $this->site,
                                trim($initiator[0]->contactEmail),
                                null,
                                \Yii::t('amend', 'submitted_screening_email_subject'),
                                str_replace('%LINK%', $amendmentLink, \Yii::t('amend', 'submitted_screening_email'))
                            );
                        } catch (MailNotSent $e) {
                        }
                    }
                }
            }

            return $this->render('create_done', ['amendment' => $amendment, 'mode' => $fromMode]);

        } else {
            $params                  = ['amendment' => $amendment, 'mode' => $fromMode];
            $params['deleteDraftId'] = (isset($_REQUEST['draftId']) ? $_REQUEST['draftId'] : null);
            return $this->render('create_confirm', $params);
        }
    }

    /**
     * @param int $motionId
     * @param int $amendmentId
     * @return string
     */
    public function actionEdit($motionId, $amendmentId)
    {
        /** @var Amendment $amendment */
        $amendment = Amendment::findOne(
            [
                'id'       => $amendmentId,
                'motionId' => $motionId,
            ]
        );
        if (!$amendment) {
            \Yii::$app->session->setFlash('error', 'Amendment not found.');
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (!$amendment->canEdit()) {
            \Yii::$app->session->setFlash('error', 'Not allowed to edit this amendment.');
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        $fromMode = ($amendment->status == Amendment::STATUS_DRAFT ? 'create' : 'edit');
        $form     = new AmendmentEditForm($amendment->motion, $amendment);

        if (isset($_POST['save'])) {
            $amendment->flushCacheWithChildren();
            $form->setAttributes([$_POST, $_FILES]);
            try {
                $form->saveAmendment($amendment);

                ConsultationLog::logCurrUser($this->consultation, ConsultationLog::AMENDMENT_CHANGE, $amendment->id);

                if ($amendment->status == Amendment::STATUS_DRAFT) {
                    $nextUrl = [
                        'amendment/createconfirm',
                        'motionId'    => $amendment->motionId,
                        'amendmentId' => $amendment->id,
                        'fromMode'    => $fromMode
                    ];
                    if (isset($_POST['draftId'])) {
                        $nextUrl['draftId'] = $_POST['draftId'];
                    }
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
     * @param int $motionId
     * @param int $adoptInitiators
     * @return string
     * @throws NotFound
     */
    public function actionCreate($motionId, $adoptInitiators = 0)
    {
        $motion = $this->consultation->getMotion($motionId);
        if (!$motion) {
            throw new NotFound(\Yii::t('motion', 'err_not_found'));
        }

        $policy = $motion->motionType->getAmendmentPolicy();
        if (!$policy->checkCurrUserAmendment()) {
            if ($policy->checkCurrUserAmendment(true, true)) {
                $loginUrl = UrlHelper::createLoginUrl(['amendment/create', 'motionId' => $motionId]);
                return $this->redirect($loginUrl);
            } else {
                return $this->showErrorpage(403, \Yii::t('amend', 'err_create_permission'));
            }
        }

        $form = new AmendmentEditForm($motion, null);

        if (isset($_POST['save'])) {
            try {
                $amendment = $form->createAmendment();
                $nextUrl   = [
                    'amendment/createconfirm',
                    'motionId'    => $amendment->motionId,
                    'amendmentId' => $amendment->id,
                    'fromMode'    => 'create'
                ];
                if (isset($_POST['draftId'])) {
                    $nextUrl['draftId'] = $_POST['draftId'];
                }
                return $this->redirect(UrlHelper::createUrl($nextUrl));
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }
        } elseif ($adoptInitiators > 0) {
            $adoptAmend = $this->consultation->getAmendment($adoptInitiators);
            $form->cloneSupporters($adoptAmend);
        }

        if (count($form->supporters) == 0) {
            $supporter       = new AmendmentSupporter();
            $supporter->role = AmendmentSupporter::ROLE_INITIATOR;
            $iAmAdmin        = User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING);
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

        if (isset($_POST['cancel'])) {
            return $this->redirect(UrlHelper::createAmendmentUrl($amendment));
        }

        if (isset($_POST['withdraw'])) {
            $amendment->withdraw();
            \Yii::$app->session->setFlash('success', \Yii::t('amend', 'widthdraw_done'));
            return $this->redirect(UrlHelper::createAmendmentUrl($amendment));
        }

        return $this->render('withdraw', ['amendment' => $amendment]);
    }
}
