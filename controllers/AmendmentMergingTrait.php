<?php

namespace app\controllers;

use app\models\settings\Privileges;
use app\models\http\{HtmlErrorResponse, HtmlResponse, RedirectResponse, ResponseInterface};
use app\components\{diff\AmendmentRewriter, diff\SingleAmendmentMergeViewParagraphData, HTMLTools, UrlHelper};
use app\models\db\{Amendment, Consultation, ConsultationMotionType};
use app\models\forms\MergeSingleAmendmentForm;
use app\models\sectionTypes\ISectionType;
use yii\web\Session;

/**
 * @method Session getHttpSession()
 * @property Consultation $consultation
 */
trait AmendmentMergingTrait
{
    public function actionGetMergeCollisions(string $motionSlug, int $amendmentId): ResponseInterface
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            return new HtmlErrorResponse(404, 'Amendment not found');
        }
        if (!$amendment->canMergeIntoMotion()) {
            $this->getHttpSession()->setFlash('error', 'Not allowed to use this function');
            return new RedirectResponse(UrlHelper::createUrl('/consultation/index'));
        }

        $otherAmendments = $amendment->getMyMotion()->getAmendmentsRelevantForCollisionDetection([$amendment]);

        if ($amendment->getMyConsultation()->havePrivilege(Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
            $otherAmendmentsStatus = $this->getPostValue('otherAmendmentsStatus', []);
        } else {
            $otherAmendmentsStatus = [];
            foreach ($otherAmendments as $newAmendment) {
                $otherAmendmentsStatus[$newAmendment->id] = $newAmendment->status;
            }
        }

        $newSectionParas = $this->getPostValue('newSections', []);
        $newSections     = [];
        foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
            $newSections[$section->sectionId] = AmendmentRewriter::calcNewSectionTextWithOverwrites(
                $section->getOriginalMotionSection()->getData(),
                $section->data,
                $newSectionParas[$section->sectionId] ?? []
            );
        }

        $collisions = $amendments = [];
        foreach ($otherAmendments as $amend) {
            if (in_array($otherAmendmentsStatus[$amend->id], $amendment->getMyConsultation()->getStatuses()->getStatusesMarkAsDoneOnRewriting())) {
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
        return new HtmlResponse($this->renderPartial('@app/views/amendment/ajax_rewrite_collisions', [
            'amendments' => $amendments,
            'collisions' => $collisions,
        ]));
    }

    public function actionMergeDone(string $motionSlug, int $amendmentId, string $newMotionId): ResponseInterface
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            return new HtmlErrorResponse(404, 'Amendment not found');
        }
        $motion = $this->consultation->getMotion($newMotionId);
        if (!$motion) {
            return new HtmlErrorResponse(404, 'Motion not found');
        }
        return new HtmlResponse($this->render('merge_done', ['amendment' => $amendment, 'newMotion' => $motion]));
    }

    public function actionMerge(string $motionSlug, int $amendmentId): ResponseInterface
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            return new HtmlErrorResponse(404, 'Amendment not found');
        }
        if (!$amendment->canMergeIntoMotion()) {
            if ($amendment->canMergeIntoMotion(true)) {
                return new HtmlResponse($this->render('merge_err_collision', [
                    'amendment'           => $amendment,
                    'collidingAmendments' => $amendment->getCollidingAmendments()
                ]));
            } else {
                $this->getHttpSession()->setFlash('error', 'Not allowed to use this function');
                return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
            }
        }

        $motion        = $amendment->getMyMotion();
        $mergingPolicy = $motion->getMyMotionType()->initiatorsCanMergeAmendments;

        if ($amendment->getMyConsultation()->havePrivilege(Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
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
                $newAmendmentStatuses = $this->getPostValue('otherAmendmentsStatus', []);
            } else {
                $newAmendmentStatuses = [];
                foreach ($motion->getAmendmentsRelevantForCollisionDetection([$amendment]) as $newAmendment) {
                    $newAmendmentStatuses[$newAmendment->id] = $newAmendment->status;
                }
            }

            if ($collisionHandling) {
                $form = new MergeSingleAmendmentForm(
                    $amendment,
                    $this->getPostValue('motionTitlePrefix'),
                    $this->getPostValue('motionVersion'),
                    $this->getPostValue('amendmentStatus'),
                    $this->getPostValue('newParas', []),
                    $this->getPostValue('amendmentOverride', []),
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
                    $this->getPostValue('motionTitlePrefix'),
                    $this->getPostValue('motionVersion'),
                    Amendment::STATUS_ACCEPTED,
                    $newParas,
                    [],
                    $newAmendmentStatuses
                );
            }
            if ($form->checkConsistency()) {
                $newMotion = $form->performRewrite();

                return new RedirectResponse(UrlHelper::createAmendmentUrl(
                    $amendment,
                    'merge-done',
                    ['newMotionId' => $newMotion->id]
                ));
            } else {
                return new HtmlErrorResponse(500, 'An internal consistance error occurred. ' .
                    'This should never happen and smells like an error in the system.');
            }
        }

        $paragraphSections = SingleAmendmentMergeViewParagraphData::createFromAmendment($amendment);

        if ($collisionHandling) {
            return new HtmlResponse($this->render('merge_with_collisions', [
                'motion'              => $motion,
                'amendment'           => $amendment,
                'paragraphSections'   => $paragraphSections,
                'allowStatusChanging' => $allowStatusChanging
            ]));
        } else {
            return new HtmlResponse($this->render('merge_without_collisions', [
                'motion'            => $motion,
                'amendment'         => $amendment,
                'paragraphSections' => $paragraphSections,
            ]));
        }
    }
}
