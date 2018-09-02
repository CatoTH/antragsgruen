<?php

namespace app\controllers;

use app\components\diff\AmendmentRewriter;
use app\components\diff\DiffRenderer;
use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\User;
use app\models\exceptions\Internal;
use app\models\exceptions\NotFound;
use app\models\forms\MergeSingleAmendmentForm;
use app\models\sectionTypes\ISectionType;

/**
 * Trait AmendmentMergingTrait
 * @package app\controllers
 *
 * @method Amendment getAmendmentWithCheck($motionSlug, $amendmentId)
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
     * @throws Internal
     * @throws NotFound
     * @throws \yii\base\ExitException
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
}
