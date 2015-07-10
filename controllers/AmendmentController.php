<?php

namespace app\controllers;

use app\components\MotionSorter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentSupporter;
use app\models\db\EMailLog;
use app\models\db\User;
use app\models\exceptions\FormError;
use app\models\exceptions\NotFound;
use app\models\forms\AmendmentEditForm;
use yii\web\Response;

class AmendmentController extends Base
{
    use AmendmentActionsTrait;

    /**
     * @param int $motionId
     * @param int $amendmentId
     * @return string
     */
    public function actionPdf($motionId, $amendmentId)
    {
        $motion    = $this->consultation->getMotion($motionId);
        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment || !$motion) {
            $this->redirect(UrlHelper::createUrl("consultation/index"));
        }
        $this->checkConsistency($motion, $amendment);

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
        $motions = MotionSorter::getSortedMotionsFlat($this->consultation, $this->consultation->motions);
        if (count($motions) == 0) {
            $this->showErrorpage(404, 'Es gibt noch keine Anträge');
        }
        $amendments = [];
        foreach ($motions as $motion) {
            foreach ($motion->amendments as $amendment) {
                if (!in_array($amendment->status, $this->consultation->getInvisibleAmendmentStati())) {
                    $amendments[] = $amendment;
                }
            }
        }
        if (count($amendments) == 0) {
            $this->showErrorpage(404, 'Es gibt noch keine Änderungsanträge');
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');
        if ($this->getParams()->xelatexPath) {
            return $this->renderPartial('pdf_collection_tex', ['amendments' => $amendments]);
        } else {
            return $this->renderPartial('pdf_collection_tcpdf', ['amendments' => $amendments]); // @TODO
        }
    }

    /**
     * @param int $motionId
     * @param int $amendmentId
     * @param int $commentId
     * @return string
     */
    public function actionView($motionId, $amendmentId, $commentId = 0)
    {
        $motion    = $this->consultation->getMotion($motionId);
        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment || !$motion) {
            $this->redirect(UrlHelper::createUrl("consultation/index"));
        }
        $this->checkConsistency($motion, $amendment);

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

        $supportStatus = "";
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
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (isset($_POST['modify'])) {
            $nextUrl = ['amendment/edit', 'amendmentId' => $amendment->id, 'motionId' => $amendment->motionId];
            $this->redirect(UrlHelper::createUrl($nextUrl));
            return '';
        }

        if (isset($_POST['confirm'])) {
            $screening = $this->consultation->getSettings()->screeningAmendments;
            if ($screening) {
                $amendment->status = Amendment::STATUS_SUBMITTED_UNSCREENED;
            } else {
                $amendment->status = Amendment::STATUS_SUBMITTED_SCREENED;
            }
            if (!$screening && $amendment->statusString == "") {
                $numbering              = $amendment->motion->consultation->getAmendmentNumbering();
                $amendment->titlePrefix = $numbering->getAmendmentNumber($amendment, $amendment->motion);
            }
            $amendment->save();

            if ($amendment->motion->consultation->adminEmail != "") {
                $mails = explode(",", $amendment->motion->consultation->adminEmail);

                $motionLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment));
                $mailText   = 'Es wurde ein neuer Änderungsantrag "%title%" eingereicht.' . "\n" . 'Link: %link%';
                $mailText   = str_replace(['%title%', '%link%'], [$amendment->getTitle(), $motionLink], $mailText);

                foreach ($mails as $mail) {
                    if (trim($mail) != "") {
                        Tools::sendMailLog(
                            EmailLog::TYPE_MOTION_NOTIFICATION_ADMIN,
                            trim($mail),
                            null,
                            'Neuer Antrag',
                            $mailText,
                            $amendment->motion->consultation->site->getBehaviorClass()->getMailFromName()
                        );
                    }
                }
            }

            if ($amendment->status == Amendment::STATUS_SUBMITTED_SCREENED) {
                $notified = [];
                foreach ($amendment->motion->consultation->subscriptions as $sub) {
                    if ($sub->motions && !in_array($sub->userId, $notified)) {
                        $sub->user->notifyAmendment($amendment);
                        $notified[] = $sub->userId;
                    }
                }
            }

            return $this->render('create_done', ['amendment' => $amendment, 'mode' => $fromMode]);

        } else {
            return $this->render('create_confirm', ['amendment' => $amendment, 'mode' => $fromMode]);
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
            $this->redirect(UrlHelper::createUrl("consultation/index"));
        }

        if (!$amendment->canEdit()) {
            \Yii::$app->session->setFlash('error', 'Not allowed to edit this amendment.');
            $this->redirect(UrlHelper::createUrl("consultation/index"));
        }

        $fromMode = ($amendment->status == Amendment::STATUS_DRAFT ? 'create' : 'edit');
        $form     = new AmendmentEditForm($amendment->motion, $amendment);

        if (isset($_POST['save'])) {
            $form->setAttributes([$_POST, $_FILES]);
            try {
                $form->saveAmendment($amendment);
                $nextUrl = [
                    'amendment/createconfirm',
                    'motionId'    => $amendment->motionId,
                    'amendmentId' => $amendment->id,
                    'fromMode'    => $fromMode
                ];
                $this->redirect(UrlHelper::createUrl($nextUrl));
                return '';
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render(
            'editform',
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
        if (!$motion || in_array($motion->status, $this->consultation->getInvisibleMotionStati())) {
            throw new NotFound('Motion not found');
        }

        if (!$motion->motionType->getMotionPolicy()->checkCurUserHeuristically()) {
            \Yii::$app->session->setFlash('error', 'Es kann kein Änderungsantrag angelegt werden.');
            $this->redirect(UrlHelper::createMotionUrl($motion));
            return '';
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
                $this->redirect(UrlHelper::createUrl($nextUrl));
                return '';
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
            if (User::getCurrentUser()) {
                $user                    = User::getCurrentUser();
                $supporter->userId       = $user->id;
                $supporter->name         = $user->name;
                $supporter->contactEmail = $user->email;
                $supporter->personType   = AmendmentSupporter::PERSON_NATURAL;
            }
            $form->supporters[] = $supporter;
        }

        return $this->render(
            'editform',
            [
                'mode'         => 'create',
                'consultation' => $this->consultation,
                'form'         => $form,
            ]
        );
    }
}
