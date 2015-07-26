<?php

namespace app\controllers;

use app\components\Mail;
use app\components\MotionSorter;
use app\components\UrlHelper;
use app\models\db\ConsultationAgendaItem;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\EMailLog;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use app\models\db\User;
use app\models\exceptions\ExceptionBase;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use app\models\forms\MotionEditForm;
use app\models\sectionTypes\ISectionType;
use yii\web\Response;

class MotionController extends Base
{
    use MotionActionsTrait;

    /**
     * @param int $motionId
     * @param int $sectionId
     * @return string
     */
    public function actionViewimage($motionId, $sectionId)
    {
        $motionId = IntVal($motionId);
        $motion   = $this->getMotionWithCheck($motionId);

        foreach ($motion->sections as $section) {
            if ($section->sectionId == $sectionId) {
                $metadata = json_decode($section->metadata, true);
                Header('Content-type: ' . $metadata['mime']);
                echo base64_decode($section->data);
                \Yii::$app->end(200);
            }
        }
        return '';
    }

    /**
     * @param int $motionId
     * @return Motion
     */
    private function getMotionWithCheck($motionId)
    {
        /** @var Motion $motion */
        $motion = Motion::findOne($motionId);
        if (!$motion) {
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        $this->checkConsistency($motion);

        return $motion;
    }

    /**
     * @param int $motionId
     * @return string
     */
    public function actionPdf($motionId)
    {
        $motionId = IntVal($motionId);
        $motion   = $this->getMotionWithCheck($motionId);

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');

        if ($this->getParams()->xelatexPath) {
            return $this->renderPartial('pdf_tex', ['motion' => $motion]);
        } else {
            return $this->renderPartial('pdf_tcpdf', ['motion' => $motion]);
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

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');
        if ($this->getParams()->xelatexPath) {
            return $this->renderPartial('pdf_collection_tex', ['motions' => $motions]);
        } else {
            return $this->renderPartial('pdf_collection_tcpdf', ['motions' => $motions]); // @TODO
        }
    }

    /**
     * @param int $motionId
     * @return string
     */
    public function actionOdt($motionId)
    {
        $motionId = IntVal($motionId);
        $motion   = $this->getMotionWithCheck($motionId);

        $filename                    = 'Motion_' . $motion->titlePrefix . '.odt';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.text');
        \yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');

        return $this->renderPartial('odt', ['motion' => $motion]);
    }


    /**
     * @param int $motionId
     * @return string
     */
    public function actionPlainhtml($motionId)
    {
        $motionId = IntVal($motionId);
        $motion   = $this->getMotionWithCheck($motionId);

        return $this->renderPartial('plain_html', ['motion' => $motion]);
    }

    /**
     * @param int $motionId
     * @param int $commentId
     * @return string
     */
    public function actionView($motionId, $commentId = 0)
    {
        $motionId = IntVal($motionId);
        $motion   = $this->getMotionWithCheck($motionId);

        $this->layout = 'column2';

        $openedComments = [];
        if ($commentId > 0) {
            foreach ($motion->sections as $section) {
                if ($section->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
                    continue;
                }
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


        if (User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            $adminEdit = UrlHelper::createUrl(['admin/motion/update', 'motionId' => $motionId]);
        } else {
            $adminEdit = null;
        }

        $commentWholeMotions = false;
        foreach ($motion->sections as $section) {
            if ($section->consultationSetting->hasComments == ConsultationSettingsMotionSection::COMMENTS_MOTION) {
                $commentWholeMotions = true;
            }
        }

        $motionViewParams = [
            'motion'              => $motion,
            'amendments'          => $motion->getVisibleAmendments(),
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
                if ($supp->userId == User::getCurrentUser()->id) {
                    $supportStatus = $supp->role;
                }
            }
        }
        $motionViewParams['supportStatus'] = $supportStatus;


        return $this->render('view', $motionViewParams);
    }


    /**
     * @param int $motionId
     * @param string $fromMode
     * @return string
     */
    public function actionCreateconfirm($motionId, $fromMode)
    {
        /** @var Motion $motion */
        $motion = Motion::findOne(
            [
                'id'             => $motionId,
                'status'         => Motion::STATUS_DRAFT,
                'consultationId' => $this->consultation->id
            ]
        );
        if (!$motion) {
            \Yii::$app->session->setFlash('error', 'Motion not found.');
            $this->redirect(UrlHelper::createUrl("consultation/index"));
        }

        if (isset($_POST['modify'])) {
            $nextUrl = ['motion/edit', 'motionId' => $motion->id];
            $this->redirect(UrlHelper::createUrl($nextUrl));
            return '';
        }

        if (isset($_POST['confirm'])) {
            $screening      = $this->consultation->getSettings()->screeningMotions;
            $motion->status = ($screening ? Motion::STATUS_SUBMITTED_UNSCREENED : Motion::STATUS_SUBMITTED_SCREENED);
            if (!$screening && $motion->statusString == '') {
                $motion->titlePrefix = $motion->consultation->getNextMotionPrefix($motion->motionTypeId);
            }
            $motion->save();

            if ($motion->consultation->adminEmail != '') {
                $mails = explode(",", $motion->consultation->adminEmail);

                $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion));
                $mailText   = "Es wurde ein neuer Antrag \"%title%\" eingereicht.\nLink: %link%";
                $mailText   = str_replace(['%title%', '%link%'], [$motion->title, $motionLink], $mailText);

                foreach ($mails as $mail) {
                    if (trim($mail) != '') {
                        Mail::sendWithLog(
                            EmailLog::TYPE_MOTION_NOTIFICATION_ADMIN,
                            $this->site,
                            trim($mail),
                            null,
                            'Neuer Antrag',
                            $mailText,
                            $motion->consultation->site->getBehaviorClass()->getMailFromName()
                        );
                    }
                }
            }

            if ($motion->status == Motion::STATUS_SUBMITTED_SCREENED) {
                $motion->onPublish();
            }

            return $this->render('create_done', ['motion' => $motion, 'mode' => $fromMode]);

        } else {
            return $this->render('create_confirm', ['motion' => $motion, 'mode' => $fromMode]);
        }
    }

    /**
     * @param int $motionId
     * @return string
     */
    public function actionEdit($motionId)
    {
        /** @var Motion $motion */
        $motion = Motion::findOne(
            [
                'id'             => $motionId,
                'consultationId' => $this->consultation->id
            ]
        );
        if (!$motion) {
            \Yii::$app->session->setFlash('error', 'Motion not found.');
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (!$motion->canEdit()) {
            \Yii::$app->session->setFlash('error', 'Not allowed to edit this motion.');
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        $form     = new MotionEditForm($motion->motionType, $motion->agendaItem, $motion);
        $fromMode = ($motion->status == Motion::STATUS_DRAFT ? 'create' : 'edit');

        if (isset($_POST['save'])) {
            $form->setAttributes([$_POST, $_FILES]);
            try {
                $form->saveMotion($motion);
                $nextUrl = ['motion/createconfirm', 'motionId' => $motion->id, 'fromMode' => $fromMode];
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
     * @param int $motionTypeId
     * @param int $agendaItemId
     * @param int $adoptInitiators
     * @return array
     * @throws Internal
     */
    private function getMotionTypeForCreate($motionTypeId = 0, $agendaItemId = 0, $adoptInitiators = 0)
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
        } elseif ($adoptInitiators > 0) {
            $motion = $this->consultation->getMotion($adoptInitiators);
            if (!$motion) {
                throw new Internal('Could not find referenced motion');
            }
            $motionType = $motion->motionType;
            $agendaItem = $motion->agendaItem;
        } else {
            throw new Internal('Could not resolve motion type');
        }

        if (!$motionType->getMotionPolicy()->checkCurUserHeuristically()) {
            throw new Internal('You do not have permissions to create a motion for this agenda item');
        }

        return [$motionType, $agendaItem];
    }


    /**
     * @param int $motionTypeId
     * @param int $agendaItemId
     * @param int $adoptInitiators
     * @return string
     */
    public function actionCreate($motionTypeId = 0, $agendaItemId = 0, $adoptInitiators = 0)
    {
        try {
            $ret = $this->getMotionTypeForCreate($motionTypeId, $agendaItemId, $adoptInitiators);
            list($motionType, $agendaItem) = $ret;
        } catch (ExceptionBase $e) {
            \Yii::$app->session->setFlash('error', $e->getMessage());
            $this->redirect(UrlHelper::createUrl('consultation/index'));
            return '';
        }

        /**
         * @var ConsultationMotionType $motionType
         * @var ConsultationAgendaItem|null $agendaItem
         */

        $policy = $motionType->getMotionPolicy();
        if (!$policy->checkMotionSubmit()) {
            if (\yii::$app->user->isGuest && $policy->checkHeuristicallyAssumeLoggedIn()) {
                $loginUrl = UrlHelper::createLoginUrl(['motion/create', 'motionTypeId' => $motionTypeId]);
                $this->redirect($loginUrl);
                return '';
            } else {
                \Yii::$app->session->setFlash('error', 'Keine Berechtigung zum Anlegen von Anträgen');
                $this->redirect(UrlHelper::createUrl('consultation/index'));
            }
        }

        $form = new MotionEditForm($motionType, $agendaItem, null);

        if (isset($_POST['save'])) {
            try {
                $motion  = $form->createMotion();
                $nextUrl = ['motion/createconfirm', 'motionId' => $motion->id, 'fromMode' => 'create'];
                $this->redirect(UrlHelper::createUrl($nextUrl));
                return '';
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }
        } elseif ($adoptInitiators > 0) {
            $motion = $this->consultation->getMotion($adoptInitiators);
            $form->cloneSupporters($motion);
        }


        if (count($form->supporters) == 0) {
            $supporter       = new MotionSupporter();
            $supporter->role = MotionSupporter::ROLE_INITIATOR;
            $iAmAdmin        = User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING);
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
            'editform',
            [
                'mode'         => 'create',
                'form'         => $form,
                'consultation' => $this->consultation,
            ]
        );
    }


    /**
     * @param int $motionId
     * @return string
     */
    public function actionWithdraw($motionId)
    {
        $motion = $this->consultation->getMotion($motionId);
        if (!$motion) {
            \Yii::$app->session->setFlash('error', 'Motion not found.');
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (!$motion->canWithdraw()) {
            \Yii::$app->session->setFlash('error', 'Not allowed to withdraw this motion.');
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (isset($_POST['cancel'])) {
            $this->redirect(UrlHelper::createMotionUrl($motion));
            return '';
        }

        if (isset($_POST['withdraw'])) {
            $motion->withdraw();
            \Yii::$app->session->setFlash('success', 'Der Antrag wurde zurückgezogen.');
            $this->redirect(UrlHelper::createMotionUrl($motion));
            return '';
        }

        return $this->render('withdraw', ['motion' => $motion]);
    }
}
