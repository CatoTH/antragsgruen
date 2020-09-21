<?php

namespace app\controllers;

use app\components\{diff\AmendmentRewriter, diff\SingleAmendmentMergeViewParagraphData, HTMLTools, UrlHelper};
use app\models\db\{Amendment, Consultation, ConsultationMotionType, User};
use app\models\exceptions\{Internal, NotFound};
use app\models\forms\MergeSingleAmendmentForm;
use app\models\sectionTypes\ISectionType;

/**
 * @method redirect($uri)
 * @property Consultation $consultation
 */
trait AmendmentMergingTrait
{
    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @return string
     * @throws NotFound
     * @throws Internal
     */
    public function actionGetMergeCollisions($motionSlug, $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            throw new NotFound('Amendment not found');
        }
        if (!$amendment->canMergeIntoMotion()) {
            \Yii::$app->session->setFlash('error', 'Not allowed to use this function');
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        $otherAmendments = $amendment->getMyMotion()->getAmendmentsRelevantForCollisionDetection([$amendment]);

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
                $section->getOriginalMotionSection()->getData(),
                $section->data,
                (isset($newSectionParas[$section->sectionId]) ? $newSectionParas[$section->sectionId] : [])
            );
        }

        $collisions = $amendments = [];
        foreach ($otherAmendments as $amend) {
            if (in_array($otherAmendmentsStatus[$amend->id], Amendment::getStatusesMarkAsDoneOnRewriting())) {
                continue;
            }
            foreach ($amend->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                $debug = false;
                $coll  = $section->getRewriteCollisions($newSections[$section->sectionId], false, $debug);

                if (count($coll) > 0) {
                    if (!isset($amendments[$amend->id])) {
                        $amendments[$amend->id] = $amend;
                        $collisions[$amend->id] = [];
                    }
                    $collisions[$amend->id][$section->sectionId] = $coll;
                }
            }
        }
        return $this->renderPartial('@app/views/amendment/ajax_rewrite_collisions', [
            'amendments' => $amendments,
            'collisions' => $collisions,
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
     * @throws Internal
     * @throws NotFound
     * @throws \app\models\exceptions\DB
     */
    public function actionMerge($motionSlug, $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            throw new NotFound('Amendment not found');
        }
        if (!$amendment->canMergeIntoMotion()) {
            if ($amendment->canMergeIntoMotion(true)) {
                return $this->render('merge_err_collision', [
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
        } elseif ($mergingPolicy == ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISION) {
            $collisionHandling   = true;
            $allowStatusChanging = false;
        } else {
            $collisionHandling   = false;
            $allowStatusChanging = false;
        }

        if ($this->isPostSet('save')) {
            if ($allowStatusChanging) {
                $newAmendmentStatuses = \Yii::$app->request->post('otherAmendmentsStatus', []);
            } else {
                $newAmendmentStatuses = [];
                foreach ($motion->getAmendmentsRelevantForCollisionDetection([$amendment]) as $newAmendment) {
                    $newAmendmentStatuses[$newAmendment->id] = $newAmendment->status;
                }
            }

            if ($collisionHandling) {
                $form = new MergeSingleAmendmentForm(
                    $amendment,
                    \Yii::$app->request->post('motionTitlePrefix'),
                    \Yii::$app->request->post('amendmentStatus'),
                    \Yii::$app->request->post('newParas', []),
                    \Yii::$app->request->post('amendmentOverride', []),
                    $newAmendmentStatuses
                );
            } else {
                $newParas = [];
                foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                    $motionParas     = HTMLTools::sectionSimpleHTML($section->getOriginalMotionSection()->getData());
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
                    $newAmendmentStatuses
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

        $paragraphSections = SingleAmendmentMergeViewParagraphData::createFromAmendment($amendment);

        if ($collisionHandling) {
            return $this->render('merge_with_collisions', [
                'motion'              => $motion,
                'amendment'           => $amendment,
                'paragraphSections'   => $paragraphSections,
                'allowStatusChanging' => $allowStatusChanging
            ]);
        } else {
            return $this->render('merge_without_collisions', [
                'motion'            => $motion,
                'amendment'         => $amendment,
                'paragraphSections' => $paragraphSections,
            ]);
        }
    }
}
