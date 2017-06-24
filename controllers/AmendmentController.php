<?php

namespace app\controllers;

use app\components\diff\AmendmentRewriter;
use app\components\diff\DiffRenderer;
use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentSupporter;
use app\models\db\ConsultationLog;
use app\models\db\ConsultationMotionType;
use app\models\db\User;
use app\models\exceptions\Access;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use app\models\exceptions\NotFound;
use app\models\forms\AmendmentEditForm;
use app\components\EmailNotifications;
use app\models\sectionTypes\ISectionType;
use app\models\forms\MergeSingleAmendmentForm;
use yii\web\Response;

class AmendmentController extends Base
{
    use AmendmentActionsTrait;

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @return Amendment|null
     */
    private function getAmendmentWithCheck($motionSlug, $amendmentId)
    {
        $motion    = $this->consultation->getMotion($motionSlug);
        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment || !$motion) {
            $this->redirect(UrlHelper::createUrl('consultation/index'));
            return null;
        }
        $this->checkConsistency($motion, $amendment);
        return $amendment;
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @return string
     */
    public function actionPdf($motionSlug, $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            return '';
        }

        $screeningPrivilege = User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING);
        if (!$amendment->isReadable() && !$screeningPrivilege) {
            return $this->render('view_not_visible', ['amendment' => $amendment, 'adminEdit' => false]);
        }

        $filename                    = $amendment->getFilenameBase(false) . '.pdf';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');
        \yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');

        if ($this->getParams()->xelatexPath && $amendment->getMyMotionType()->texTemplateId) {
            return $this->renderPartial('pdf_tex', ['amendment' => $amendment]);
        } else {
            return $this->renderPartial('pdf_tcpdf', ['amendment' => $amendment]);
        }
    }

    /**
     * @param int $withdrawn
     * @return string
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
        if ($this->getParams()->xelatexPath && $texTemplate) {
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
     */
    public function actionOdt($motionSlug, $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            return '';
        }

        $screeningPrivilege = User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING);
        if (!$amendment->isReadable() && !$screeningPrivilege) {
            return $this->render('view_not_visible', ['amendment' => $amendment, 'adminEdit' => false]);
        }

        $filename                    = $amendment->getFilenameBase(false) . '.odt';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.text');
        \yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');

        return \app\views\amendment\LayoutHelper::createOdt($amendment);
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @param int $commentId
     * @return string
     */
    public function actionView($motionSlug, $amendmentId, $commentId = 0)
    {
        $this->layout = 'column2';

        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            return '';
        }

        if (User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            $adminEdit = UrlHelper::createUrl(['admin/amendment/update', 'amendmentId' => $amendmentId]);
        } else {
            $adminEdit = null;
        }

        $screeningPrivilege = User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING);
        if (!$amendment->isReadable() && !$screeningPrivilege) {
            return $this->render('view_not_visible', ['amendment' => $amendment, 'adminEdit' => $adminEdit]);
        }

        $openedComments      = [];
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
            $amendment->setInitialSubmitted();

            if ($amendment->status == Amendment::STATUS_SUBMITTED_SCREENED) {
                $amendment->onPublish();
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
     * @throws Access
     * @throws NotFound
     */
    public function actionGetMergeCollissions($motionSlug, $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            throw new NotFound('Amendment not found');
        }
        if (!$amendment->canMergeIntoMotion()) {
            \Yii::$app->session->setFlash('error', 'Not allowed to use this function');
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        $otherAmendments = $amendment->getMyMotion()->getAmendmentsRelevantForCollissionDetection([$amendment]);

        if ($amendment->getMyConsultation()->havePrivilege(User::PRIVILEGE_CONTENT_EDIT)) {
            $otherAmendmentsStatus = \Yii::$app->request->post('otherAmendmentsStatus', []);
        } else {
            $otherAmendmentsStatus = [];
            foreach ($otherAmendments as $newAmendment) {
                $otherAmendmentsStatus[$newAmendment->id] = $newAmendment->status;
            }
        }

        $newSectionParas = \Yii::$app->request->post('newSections', []);
        $newSections     = [];
        foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
            $newSections[$section->sectionId] = AmendmentRewriter::calcNewSectionTextWithOverwrites(
                $section->getOriginalMotionSection()->data,
                $section->data,
                (isset($newSectionParas[$section->sectionId]) ? $newSectionParas[$section->sectionId] : [])
            );
        }

        $collissions = $amendments = [];
        foreach ($otherAmendments as $amend) {
            if (in_array($otherAmendmentsStatus[$amend->id], Amendment::getStatiMarkAsDoneOnRewriting())) {
                continue;
            }
            foreach ($amend->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                $debug = false;
                $coll  = $section->getRewriteCollissions($newSections[$section->sectionId], false, $debug);

                if (count($coll) > 0) {
                    if (!in_array($amend, $amendments)) {
                        $amendments[$amend->id]  = $amend;
                        $collissions[$amend->id] = [];
                    }
                    $collissions[$amend->id][$section->sectionId] = $coll;
                }
            }
        }
        return $this->renderPartial('@app/views/amendment/ajax_rewrite_collissions', [
            'amendments'  => $amendments,
            'collissions' => $collissions,
        ]);
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @param int $newMotionId
     * @return string
     * @throws NotFound
     */
    public function actionMergeDone($motionSlug, $amendmentId, $newMotionId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            throw new NotFound('Amendment not found');
        }
        $motion = $this->consultation->getMotion($newMotionId);
        if (!$motion) {
            throw new NotFound('Motion not found');
        }
        return $this->render('merge_done', ['amendment' => $amendment, 'newMotion' => $motion]);
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @return string
     * @throws Access
     * @throws Internal
     * @throws NotFound
     */
    public function actionMerge($motionSlug, $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            throw new NotFound('Amendment not found');
        }
        if (!$amendment->canMergeIntoMotion()) {
            if ($amendment->canMergeIntoMotion(true)) {
                return $this->render('merge_err_collission', [
                    'amendment'           => $amendment,
                    'collidingAmendments' => $amendment->getCollidingAmendments()
                ]);
            } else {
                \Yii::$app->session->setFlash('error', 'Not allowed to use this function');
                return $this->redirect(UrlHelper::createUrl('consultation/index'));
            }
        }

        $motion        = $amendment->getMyMotion();
        $mergingPolicy = $motion->getMyMotionType()->initiatorsCanMergeAmendments;

        if ($amendment->getMyConsultation()->havePrivilege(User::PRIVILEGE_CONTENT_EDIT)) {
            $collisionHandling   = true;
            $allowStatusChanging = true;
        } elseif ($mergingPolicy == ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISSION) {
            $collisionHandling   = true;
            $allowStatusChanging = false;
        } else {
            $collisionHandling   = false;
            $allowStatusChanging = false;
        }

        if ($this->isPostSet('save')) {
            if ($allowStatusChanging) {
                $newAmendmentStati = \Yii::$app->request->post('otherAmendmentsStatus', []);
            } else {
                $newAmendmentStati = [];
                foreach ($motion->getAmendmentsRelevantForCollissionDetection([$amendment]) as $newAmendment) {
                    $newAmendmentStati[$newAmendment->id] = $newAmendment->status;
                }
            }

            if ($collisionHandling) {
                $form = new MergeSingleAmendmentForm(
                    $amendment,
                    \Yii::$app->request->post('motionTitlePrefix'),
                    \Yii::$app->request->post('amendmentStatus'),
                    \Yii::$app->request->post('newParas', []),
                    \Yii::$app->request->post('amendmentOverride', []),
                    $newAmendmentStati
                );
            } else {
                $newParas = [];
                foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                    $motionParas     = HTMLTools::sectionSimpleHTML($section->getOriginalMotionSection()->data);
                    $amendParas      = HTMLTools::sectionSimpleHTML($section->data);
                    $paragraphsPlain = AmendmentRewriter::computeAffectedParagraphs($motionParas, $amendParas, false);

                    $newParas[$section->sectionId] = $paragraphsPlain;
                }
                $form = new MergeSingleAmendmentForm(
                    $amendment,
                    \Yii::$app->request->post('motionTitlePrefix'),
                    Amendment::STATUS_ACCEPTED,
                    $newParas,
                    [],
                    $newAmendmentStati
                );
            }
            if ($form->checkConsistency()) {
                $newMotion = $form->performRewrite();

                return $this->redirect(UrlHelper::createAmendmentUrl(
                    $amendment,
                    'merge-done',
                    ['newMotionId' => $newMotion->id]
                ));
            } else {
                return $this->showErrorpage(500, 'An internal consistance error occurred. ' .
                    'This should never happen and smells like an error in the system.');
            }
        }

        $paragraphSections = [];
        $diffRenderer      = new DiffRenderer();
        $diffRenderer->setFormatting(DiffRenderer::FORMATTING_CLASSES);

        foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
            $motionParas     = HTMLTools::sectionSimpleHTML($section->getOriginalMotionSection()->data);
            $amendmentParas  = HTMLTools::sectionSimpleHTML($section->data);
            $paragraphsDiff  = AmendmentRewriter::computeAffectedParagraphs($motionParas, $amendmentParas, true);
            $paragraphsPlain = AmendmentRewriter::computeAffectedParagraphs($motionParas, $amendmentParas, false);

            $paraLineNumbers = $section->getParagraphLineNumberHelper();
            $paragraphs      = [];
            foreach (array_keys($paragraphsDiff) as $paraNo) {
                $paragraphs[$paraNo] = [
                    'lineFrom' => $paraLineNumbers[$paraNo],
                    'lineTo'   => $paraLineNumbers[$paraNo + 1] - 1,
                    'plain'    => $paragraphsPlain[$paraNo],
                    'diff'     => $diffRenderer->renderHtmlWithPlaceholders($paragraphsDiff[$paraNo]),
                ];
            }

            $paragraphSections[$section->sectionId] = $paragraphs;
        }

        if ($collisionHandling) {
            return $this->render('merge_with_collissions', [
                'motion'              => $motion,
                'amendment'           => $amendment,
                'paragraphSections'   => $paragraphSections,
                'allowStatusChanging' => $allowStatusChanging
            ]);
        } else {
            return $this->render('merge_without_collissions', [
                'motion'            => $motion,
                'amendment'         => $amendment,
                'paragraphSections' => $paragraphSections,
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
            $amendment->flushCacheWithChildren();
            $form->setAttributes([\Yii::$app->request->post(), $_FILES]);
            try {
                $form->saveAmendment($amendment);

                ConsultationLog::logCurrUser($this->consultation, ConsultationLog::AMENDMENT_CHANGE, $amendment->id);

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
     * @throws NotFound
     */
    public function actionCreate($motionSlug, $cloneFrom = 0)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            throw new NotFound(\Yii::t('motion', 'err_not_found'));
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
        $iAmAdmin    = User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING);

        if ($this->isPostSet('save')) {
            try {
                $amendment = $form->createAmendment();

                // Supporting members are not collected in the form, but need to be copied a well
                if ($supportType->collectSupportersBeforePublication() && $cloneFrom && $iAmAdmin) {
                    $adoptAmend = $this->consultation->getAmendment($cloneFrom);
                    foreach ($adoptAmend->amendmentSupporters as $supp) {
                        if ($supp->role == AmendmentSupporter::ROLE_SUPPORTER) {
                            $suppNew = new AmendmentSupporter();
                            $suppNew->setAttributes($supp->getAttributes());
                            $suppNew->id          = null;
                            $suppNew->amendmentId = $amendment->id;
                            $suppNew->save();
                        }
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
            $supporter       = new AmendmentSupporter();
            $supporter->role = AmendmentSupporter::ROLE_INITIATOR;
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
}
