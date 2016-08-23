<?php

namespace app\controllers;

use app\components\UrlHelper;
use app\components\EmailNotifications;
use app\models\db\ConsultationAgendaItem;
use app\models\db\ConsultationLog;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use app\models\db\User;
use app\models\exceptions\ExceptionBase;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use app\models\forms\MotionEditForm;
use app\models\forms\MotionMergeAmendmentsForm;
use app\models\sectionTypes\ISectionType;
use yii\web\Response;

class MotionController extends Base
{
    use MotionActionsTrait;

    /**
     * @param string $motionSlug
     * @param int $sectionId
     * @return string
     */
    public function actionViewimage($motionSlug, $sectionId)
    {
        $motion = $this->getMotionWithCheck($motionSlug);

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
     * @param string $motionSlug
     * @param int $sectionId
     * @return string
     */
    public function actionViewpdf($motionSlug, $sectionId)
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!$motion->isReadable() && !User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
        }

        foreach ($motion->sections as $section) {
            if ($section->sectionId == $sectionId) {
                Header('Content-type: application/pdf');
                echo base64_decode($section->data);
                \Yii::$app->end(200);
            }
        }
        return '';
    }

    /**
     * @return string
     */
    public function actionEmbeddedpdf()
    {
        return $this->renderPartial('pdf_embed', []);
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
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));
            $this->redirect(UrlHelper::createUrl('consultation/index'));
            \Yii::$app->end();
            return null;
        }

        $this->checkConsistency($motion);

        return $motion;
    }

    /**
     * @param string $motionSlug
     * @return string
     */
    public function actionPdf($motionSlug)
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!$motion->isReadable() && !User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
        }

        $filename                    = $motion->getFilenameBase(false) . '.pdf';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');
        \yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');

        if ($this->getParams()->xelatexPath) {
            return $this->renderPartial('pdf_tex', ['motion' => $motion]);
        } else {
            return $this->renderPartial('pdf_tcpdf', ['motion' => $motion]);
        }
    }

    /**
     * @param string $motionTypeId
     * @param int $withdrawn
     * @return string
     */
    public function actionPdfcollection($motionTypeId = '', $withdrawn = 0)
    {
        $withdrawn = ($withdrawn == 1);
        try {
            $motions = $this->consultation->getVisibleMotionsSorted($withdrawn);
            if ($motionTypeId != '' && $motionTypeId != '0') {
                $motionTypeIds   = explode(',', $motionTypeId);
                $motionsFiltered = [];
                foreach ($motions as $motion) {
                    if (in_array($motion->motionTypeId, $motionTypeIds)) {
                        $motionsFiltered[] = $motion;
                    }
                }
                $motions = $motionsFiltered;
            }
            if (count($motions) == 0) {
                return $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
            }
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
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
     * @param string $motionSlug
     * @return string
     */
    public function actionOdt($motionSlug)
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!$motion->isReadable() && !User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
        }

        $filename                    = $motion->getFilenameBase(false) . '.odt';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.text');
        \yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');

        return \app\views\motion\LayoutHelper::createOdt($motion);
    }


    /**
     * @param string $motionSlug
     * @return string
     */
    public function actionPlainhtml($motionSlug)
    {
        $motion = $this->getMotionWithCheck($motionSlug);

        if (!$motion->isReadable() && !User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => false]);
        }

        return $this->renderPartial('plain_html', ['motion' => $motion]);
    }

    /**
     * @param string $motionSlug
     * @param int $commentId
     * @return string
     */
    public function actionView($motionSlug, $commentId = 0)
    {
        $this->layout = 'column2';

        $motion = $this->getMotionWithCheck($motionSlug);
        if (User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            $adminEdit = UrlHelper::createUrl(['admin/motion/update', 'motionId' => $motion->id]);
        } else {
            $adminEdit = null;
        }

        if (!$this->wordpressMode) {
            $this->layout = 'column2';
        }

        if (!$motion->isReadable()) {
            return $this->render('view_not_visible', ['motion' => $motion, 'adminEdit' => $adminEdit]);
        }

        $openedComments = [];
        if ($commentId > 0) {
            foreach ($motion->sections as $section) {
                if ($section->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
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


        $commentWholeMotions = false;
        foreach ($motion->sections as $section) {
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
                if ($supp->userId == User::getCurrentUser()->id) {
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
    public function actionConsolidated($motionSlug)
    {
        return $this->actionView($motionSlug, 0);
    }


    /**
     * @param string $motionSlug
     * @param string $fromMode
     * @return string
     */
    public function actionCreateconfirm($motionSlug, $fromMode)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion || $motion->status != Motion::STATUS_DRAFT) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if ($this->isPostSet('modify')) {
            return $this->redirect(UrlHelper::createMotionUrl($motion, 'edit'));
        }

        if ($this->isPostSet('confirm')) {
            $motion->setInitialSubmitted();

            if ($motion->status == Motion::STATUS_SUBMITTED_SCREENED) {
                $motion->onPublish();
            } else {
                EmailNotifications::sendMotionSubmissionConfirm($motion);
            }

            return $this->render('create_done', ['motion' => $motion, 'mode' => $fromMode]);

        } else {
            $params                  = ['motion' => $motion, 'mode' => $fromMode];
            $params['deleteDraftId'] = $this->getRequestValue('draftId');
            return $this->render('create_confirm', $params);
        }
    }

    /**
     * @param string $motionSlug
     * @return string
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
            $motion->flushCacheWithChildren();
            $form->setAttributes([\Yii::$app->request->post(), $_FILES]);
            try {
                $form->saveMotion($motion);

                ConsultationLog::logCurrUser($this->consultation, ConsultationLog::MOTION_CHANGE, $motion->id);

                if ($motion->status == Motion::STATUS_DRAFT) {
                    $nextUrl = UrlHelper::createMotionUrl($motion, 'createconfirm', ['fromMode' => $fromMode]);
                    return $this->redirect($nextUrl);
                } else {
                    return $this->render('edit_done', ['motion' => $motion]);
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
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        /**
         * @var ConsultationMotionType $motionType
         * @var ConsultationAgendaItem|null $agendaItem
         */

        $policy = $motionType->getMotionPolicy();
        if (!$policy->checkCurrUserMotion()) {
            if ($policy->checkCurrUserMotion(true, true)) {
                $loginUrl = UrlHelper::createLoginUrl(['motion/create', 'motionTypeId' => $motionTypeId]);
                return $this->redirect($loginUrl);
            } else {
                return $this->showErrorpage(403, \Yii::t('motion', 'err_create_permission'));
            }
        }

        $form = new MotionEditForm($motionType, $agendaItem, null);

        if ($this->isPostSet('save')) {
            try {
                $motion = $form->createMotion();
                return $this->redirect(UrlHelper::createMotionUrl($motion, 'createconfirm', [
                    'fromMode' => 'create',
                    'draftId'  => $this->getRequestValue('draftId'),
                ]));
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
     * @param string $amendmentStati
     * @return string
     */
    public function actionMergeamendmentconfirm($motionSlug, $amendmentStati = '')
    {
        $newMotion = $this->consultation->getMotion($motionSlug);
        if (!$newMotion || $newMotion->status != Motion::STATUS_DRAFT || !$newMotion->replacedMotion) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }
        $oldMotion  = $newMotion->replacedMotion;
        $amendStati = ($amendmentStati == '' ? [] : json_decode($amendmentStati, true));

        if ($this->isPostSet('modify')) {
            return $this->redirect(UrlHelper::createMotionUrl($oldMotion, 'mergeamendments', [
                'newMotionId'    => $newMotion->id,
                'amendmentStati' => $amendmentStati
            ]));
        }

        if ($this->isPostSet('confirm')) {
            $invisible = $this->consultation->getInvisibleAmendmentStati();
            foreach ($oldMotion->getVisibleAmendments() as $amendment) {
                if (isset($amendStati[$amendment->id]) && $amendStati[$amendment->id] != $amendment->status) {
                    if (!in_array($amendStati[$amendment->id], $invisible)) {
                        $amendment->status = $amendStati[$amendment->id];
                        $amendment->save();
                    }
                }
            }

            $screening         = $this->consultation->getSettings()->screeningMotions;
            $iAmAdmin          = User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING);
            $newMotion->status = Motion::STATUS_SUBMITTED_UNSCREENED;
            if (!$screening || $iAmAdmin) {
                $newMotion->status = Motion::STATUS_SUBMITTED_SCREENED;
            }
            $newMotion->save();

            $newMotion->status = $newMotion->replacedMotion->status;
            $newMotion->save();

            if ($newMotion->replacedMotion->status == Motion::STATUS_SUBMITTED_SCREENED) {
                $newMotion->replacedMotion->status = Motion::STATUS_MODIFIED;
                $newMotion->replacedMotion->save();
            }

            $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($newMotion));

            $mailText = \Yii::t('motion', 'edit_mail_body');
            $mailText = str_replace(['%TITLE%', '%LINK%'], [$newMotion->title, $motionLink], $mailText);
            $newMotion->getConsultation()->sendEmailToAdmins(\Yii::t('motion', 'edit_mail_title'), $mailText);

            return $this->render('merge_amendments_done', ['newMotion' => $newMotion]);
        }

        $draftId = null;
        return $this->render('merge_amendments_confirm', [
            'newMotion'      => $newMotion,
            'deleteDraftId'  => $draftId,
            'amendmentStati' => $amendStati
        ]);
    }

    /**
     * @param string $motionSlug
     * @param int $newMotionId
     * @param string $amendmentStati
     * @return string
     */
    public function actionMergeamendments($motionSlug, $newMotionId = 0, $amendmentStati = '')
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (!$motion->canMergeAmendments()) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_edit_permission'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if ($newMotionId > 0) {
            $newMotion = $this->consultation->getMotion($newMotionId);
            if (!$newMotion || $newMotion->parentMotionId != $motion->id) {
                \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));
                return $this->redirect(UrlHelper::createMotionUrl($motion));
            }
        } else {
            $newMotion                 = new Motion();
            $newMotion->motionTypeId   = $motion->motionTypeId;
            $newMotion->agendaItemId   = $motion->agendaItemId;
            $newMotion->consultationId = $motion->consultationId;
            $newMotion->parentMotionId = $newMotion->id;
            $newMotion->refresh();
        }

        $amendStati = ($amendmentStati == '' ? [] : json_decode($amendmentStati, true));
        $form       = new MotionMergeAmendmentsForm($motion, $newMotion);

        try {
            if ($this->isPostSet('save')) {
                $form->setAttributes(\Yii::$app->request->post());
                try {
                    $newMotion = $form->createNewMotion();
                    return $this->redirect(UrlHelper::createMotionUrl($newMotion, 'mergeamendmentconfirm', [
                        'fromMode'       => 'create',
                        'amendmentStati' => json_encode($form->amendStatus),
                        'draftId'        => $this->getRequestValue('draftId'),
                    ]));
                } catch (FormError $e) {
                    \Yii::$app->session->setFlash('error', $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            \yii::$app->session->setFlash('error', $e->getMessage());
        }

        $params = ['motion' => $motion, 'form' => $form, 'amendmentStati' => $amendStati];
        return $this->render('merge_amendments', $params);
    }
}
