<?php

namespace app\controllers;

use app\models\db\{Motion, MotionSection, Consultation};
use app\models\http\{BinaryFileResponse,
    HtmlErrorResponse,
    HtmlResponse,
    JsonResponse,
    RedirectResponse,
    ResponseInterface};
use app\components\{MotionSorter, UrlHelper};
use app\models\exceptions\Inconsistency;
use app\models\mergeAmendments\{Draft, Merge, Init};
use app\models\MotionSectionChanges;
use yii\web\{Request, Response, Session};

/**
 * @property Consultation $consultation
 * @method Session getHttpSession()
 * @method Response getHttpResponse()
 * @method Request getHttpRequest()
 */
trait MotionMergingTrait
{
    public function actionMergeAmendmentsPublic(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_not_found'));

            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        $draft = $motion->getMergingDraft(true);
        if (!$draft) {
            return new HtmlErrorResponse(404, \Yii::t('motion', 'err_draft_not_found'));
        }

        return new HtmlResponse($this->render('@app/views/merging/public_version', ['motion' => $motion, 'draft' => $draft]));
    }

    public function actionMergeAmendmentsPublicAjax(string $motionSlug): JsonResponse
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new JsonResponse(['success' => false, 'error' => \Yii::t('motion', 'err_not_found')]);
        }

        $draft = $motion->getMergingDraft(true);
        if (!$draft) {
            return new JsonResponse(['success' => false, 'error' => \Yii::t('motion', 'err_draft_not_found')]);
        }

        return new JsonResponse([
            'success' => true,
            'html'    => $this->renderPartial('@app/views/merging/_public_version_content', [
                'motion' => $motion,
                'draft'  => $draft
            ]),
            'date'    => ($draft->draftMotion->getDateTime() ? $draft->draftMotion->getDateTime()->format('c') : ''),
        ]);
    }

    public function actionMergeAmendmentsParagraphAjax(string $motionSlug, int $sectionId, int $paragraphNo, string $amendments = ''): JsonResponse
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new JsonResponse(['success' => false, 'error' => \Yii::t('motion', 'err_not_found')]);
        }


        $section = null;
        foreach ($motion->getActiveSections() as $sec) {
            if ($sec->sectionId === $sectionId) {
                $section = $sec;
            }
        }
        if (!$section) {
            return new JsonResponse(['success' => false, 'error' => \Yii::t('motion', 'err_not_found')]);
        }

        $amendments   = json_decode($amendments, true);
        $amendmentIds = [];
        foreach ($amendments as $amendment) {
            if ($amendment['version'] === 'prop') {
                $amendmentIds[] = $this->consultation->getAmendment($amendment['id'])->getMyProposalReference()->id;
            } else {
                $amendmentIds[] = $amendment['id'];
            }
        }

        $amendmentsById = [];
        foreach ($section->getMergingAmendingSections(false, true) as $sect) {
            $amendmentsById[$sect->amendmentId] = $sect->getAmendment();
        }

        $merger        = $section->getAmendmentDiffMerger($amendmentIds)->getParagraphMerger(IntVal($paragraphNo));
        $paragraphText = $merger->getFormattedDiffText($amendmentsById);
        $collisions    = [];

        $paragraphCollisions = $merger->getCollidingParagraphGroups();
        foreach ($paragraphCollisions as $amendmentId => $paraData) {
            $amendment    = $amendmentsById[$amendmentId];
            $collisions[] = $merger->getFormattedCollision($paraData, $amendment, $amendmentsById, true);
        }

        return new JsonResponse([
            'text'       => $paragraphText,
            'collisions' => $collisions,
        ]);
    }

    public function actionMergeAmendmentsStatusAjax(string $motionSlug, string $knownAmendments): JsonResponse
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new JsonResponse(['success' => false, 'error' => \Yii::t('motion', 'err_not_found')]);
        }

        $amendmentsById = [];
        $newAmendmentsById = [];
        $newAmendmentsStaticData = [];
        $newAmendmentsStatus = [];

        $knownAmendments = array_map('intval', explode(',', $knownAmendments));
        $amendments = Init::getMotionAmendmentsForMerging($motion);
        $proposedAlternative = $motion->getAlternativeProposaltextReference();
        if ($proposedAlternative && $proposedAlternative['motion']->id === $motion->id) {
            $amendments[] = $proposedAlternative['modification'];
        }

        foreach ($amendments as $amendment) {
            $amendmentsById[$amendment->id] = $amendment;
            if (!in_array($amendment->id, $knownAmendments)) {
                $newAmendmentsById[$amendment->id]   = $amendment;
                $newAmendmentsStaticData[]           = Init::getJsAmendmentStaticData($amendment);
                $newAmendmentsStatus[$amendment->id] = [
                    'status'     => $amendment->status,
                    'version'    => ($amendment->hasAlternativeProposaltext(false) ? Init::TEXT_VERSION_PROPOSAL : Init::TEXT_VERSION_ORIGINAL),
                    'votingData' => $amendment->getVotingData()->jsonSerialize(),
                ];
            }
        }

        $deletedAmendmentIds = [];
        foreach ($knownAmendments as $amendmentId) {
            if (!isset($amendmentsById[$amendmentId])) {
                $deletedAmendmentIds[] = $amendmentId;
            }
        }

        $newAmendmentsParagraphs = [];
        if (count($newAmendmentsStaticData) > 0) {
            // Init::fromInitForm is computational heavy, therefore only call it if something new comes in
            $form = Init::fromInitForm($motion, [], []);

            foreach ($motion->getSortedSections(false) as $section) {
                /** @var MotionSection $section */
                $type = $section->getSettings();
                $newAmendmentsParagraphs[$type->id] = [];
                // @TODO Support titles?
                if ($type->type === \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
                    $paragraphs   = $section->getTextParagraphObjects(false, false, false, true);
                    $paragraphNos = array_keys($paragraphs);
                    foreach ($paragraphNos as $paragraphNo) {
                        $newAmendmentsParagraphs[$type->id][$paragraphNo] = $form->getJsParagraphStatusData($section, $paragraphNo, $newAmendmentsById);
                    }
                }
            }
        }

        return new JsonResponse([
            'success' => true,
            'new'     => [
                'staticData' => $newAmendmentsStaticData,
                'status'     => $newAmendmentsStatus,
                'paragraphs' => $newAmendmentsParagraphs,
            ],
            'deleted' => $deletedAmendmentIds,
        ]);
    }

    public function actionMergeAmendmentsDraftPdf(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404, \Yii::t('motion', 'err_not_found'));
        }

        if (!$motion->canMergeAmendments()) {
            return new HtmlErrorResponse(403, \Yii::t('motion', 'err_edit_permission'));
        }

        $draft = $motion->getMergingDraft(false);
        if (!$draft) {
            return new HtmlErrorResponse(404, \Yii::t('motion', 'err_draft_not_found'));
        }

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_PDF,
            $this->render('@app/views/merging/merging_draft_pdf', ['motion' => $motion, 'draft' => $draft]),
            true,
            $motion->getFilenameBase(false) . '-Merging-Draft',
            false
        );
    }

    public function actionMergeAmendmentsInit(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_not_found'));

            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        if (!$motion->canMergeAmendments()) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_edit_permission'));

            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        $amendments = Init::getMotionAmendmentsForMerging($motion);
        $amendments = MotionSorter::getSortedAmendments($motion->getMyConsultation(), $amendments);

        $draft       = $motion->getMergingDraft(false);
        $unconfirmed = $motion->getMergingUnconfirmed();

        if (count($amendments) === 0 && !$draft && !$unconfirmed) {
            return new RedirectResponse(UrlHelper::createMotionUrl($motion, 'merge-amendments'));
        }

        return new HtmlResponse($this->render('@app/views/merging/init', [
            'motion'      => $motion,
            'amendments'  => $amendments,
            'draft'       => $draft,
            'unconfirmed' => $unconfirmed,
        ]));
    }

    private function getMotionForMerging(string $motionSlug): ?Motion
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_not_found'));

            return null;
        }

        if (!$motion->canMergeAmendments()) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_edit_permission'));

            return null;
        }

        return $motion;
    }

    public function actionMergeAmendmentsInitPdf(string $motionSlug, string $activated = ''): ResponseInterface
    {
        $motion = $this->getMotionForMerging($motionSlug);
        if (!$motion) {
            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        $amendments   = $motion->getVisibleAmendmentsSorted();
        $activatedIds = [];
        foreach (explode(',', $activated) as $active) {
            if ($active > 0) {
                $activatedIds[] = intval($active);
            }
        }

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_PDF,
            $this->render('@app/views/merging/init_pdf', [
                'motion'     => $motion,
                'amendments' => $amendments,
                'activated'  => $activatedIds,
            ]),
            true,
            $motion->getFilenameBase(false) . '-Merging-Selection',
            false
        );
    }

    public function actionMergeAmendmentsConfirm(string $motionSlug): ResponseInterface
    {
        $newMotion = $this->consultation->getMotion($motionSlug);
        if (!$newMotion) {
            return new HtmlErrorResponse(404, \Yii::t('motion', 'err_not_found'));
        }

        $oldMotion = $newMotion->replacedMotion;
        if (!$oldMotion->canMergeAmendments()) {
            return new HtmlErrorResponse(403, \Yii::t('motion', 'err_edit_permission'));
        }

        if ($this->isPostSet('modify')) {
            $merger = new Merge($oldMotion);
            $merger->updateDraftOnBackToModify(
                array_map('intval', $this->getHttpRequest()->post('amendStatus', [])),
                $this->getHttpRequest()->post('amendVotes', [])
            );

            return new RedirectResponse(UrlHelper::createMotionUrl($oldMotion, 'merge-amendments'));
        }

        if ($this->isPostSet('confirm')) {
            $merger = new Merge($oldMotion);
            $merger->confirm(
                $newMotion,
                array_map('intval', $this->getHttpRequest()->post('amendStatus', [])),
                $this->getHttpRequest()->post('newStatus'),
                $this->getHttpRequest()->post('newSubstatus'),
                $this->getHttpRequest()->post('newInitiator', ''),
                $this->getHttpRequest()->post('newMotionType'),
                $this->getHttpRequest()->post('votes', []),
                $this->getHttpRequest()->post('amendVotes', [])
            );

            return new HtmlResponse($this->render('@app/views/merging/done', [
                'newMotion' => $newMotion,
            ]));
        }

        try {
            $changes = MotionSectionChanges::motionToSectionChanges($oldMotion, $newMotion);
        } catch (Inconsistency $e) {
            $changes = [];
        }

        $mergingDraft = $oldMotion->getMergingDraft(false);

        return new HtmlResponse($this->render('@app/views/merging/confirm', [
            'oldMotion'    => $oldMotion,
            'newMotion'    => $newMotion,
            'mergingDraft' => $mergingDraft,
            'changes'      => $changes,
        ]));
    }

    public function actionMergeAmendments(string $motionSlug): ResponseInterface
    {
        $motion = $this->getMotionForMerging($motionSlug);
        if (!$motion) {
            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        $resumeDraft = $motion->getMergingDraft(false);

        try {
            if ($this->isPostSet('save')) {
                $public = ($resumeDraft ? $resumeDraft->public : false);
                $draft  = Draft::initFromJson($motion, $public, new \DateTime('now'), $this->getHttpRequest()->post('mergeDraft', null));
                $draft->save();

                $form      = new Merge($motion);
                $newMotion = $form->createNewMotion($draft);

                return new RedirectResponse(UrlHelper::createMotionUrl($newMotion, 'merge-amendments-confirm'));
            }
        } catch (\Exception $e) {
            $this->getHttpSession()->setFlash('error', $e->getMessage());
        }

        if ($resumeDraft && !$this->getHttpRequest()->post('discard', 0) && count($resumeDraft->sections) === 1) {
            $form = Init::initFromDraft($motion, $resumeDraft);
        } else {
            $form = Init::fromInitForm($motion,
                $this->getHttpRequest()->post('amendments', []),
                $this->getHttpRequest()->post('textVersion', [])
            );
        }

        $twoCols = $motion->getMyMotionType()->getSettingsObj()->twoColMerging;

        return new HtmlResponse($this->render('@app/views/merging/merging', ['form' => $form, 'twoCols' => $twoCols]));
    }

    public function actionSaveMergingDraft(string $motionSlug): JsonResponse
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new JsonResponse(['success' => false, 'error' => 'Motion not found']);
        }
        if (!$motion->canMergeAmendments()) {
            return new JsonResponse(['success' => false, 'error' => 'Motion not editable']);
        }

        $public = (IntVal($this->getHttpRequest()->post('public', 0)) === 1);
        $draft  = Draft::initFromJson($motion, $public, new \DateTime('now'), $this->getHttpRequest()->post('data', null));
        $draft->save();

        return new JsonResponse([
            'success' => true,
            'date'    => ($draft->draftMotion->getDateTime() ? $draft->draftMotion->getDateTime()->format('c') : ''),
        ]);
    }
}
