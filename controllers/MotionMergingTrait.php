<?php

namespace app\controllers;

use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\exceptions\{Inconsistency, Internal};
use app\models\mergeAmendments\{Draft, Merge, Init};
use app\models\MotionSectionChanges;
use yii\web\Response;

/**
 * Trait MotionMergingTrait
 * @package controllers
 * @property Consultation $consultation
 */
trait MotionMergingTrait
{
    /**
     * @param string $motionSlug
     *
     * @return string
     */
    public function actionMergeAmendmentsPublic($motionSlug)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));

            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        $draft = $motion->getMergingDraft(true);
        if (!$draft) {
            return $this->showErrorpage(404, \Yii::t('motion', 'err_draft_not_found'));
        }

        return $this->render('@app/views/merging/public_version', ['motion' => $motion, 'draft' => $draft]);
    }

    /**
     * @param string $motionSlug
     *
     * @return string
     * @throws Internal
     */
    public function actionMergeAmendmentsPublicAjax($motionSlug)
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return json_encode(['success' => false, 'error' => \Yii::t('motion', 'err_not_found')]);
        }

        $draft = $motion->getMergingDraft(true);
        if (!$draft) {
            return json_encode(['success' => false, 'error' => \Yii::t('motion', 'err_draft_not_found')]);
        }

        return json_encode([
            'success' => true,
            'html'    => $this->renderPartial('@app/views/merging/_public_version_content', [
                'motion' => $motion,
                'draft'  => $draft
            ]),
            'date'    => ($draft->draftMotion->getDateTime() ? $draft->draftMotion->getDateTime()->format('c') : ''),
        ]);
    }

    /**
     * @param string $motionSlug
     * @param int $sectionId
     * @param int $paragraphNo
     * @param string $amendments
     *
     * @return string
     */
    public function actionMergeAmendmentsParagraphAjax($motionSlug, $sectionId, $paragraphNo, $amendments = '')
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return json_encode(['success' => false, 'error' => \Yii::t('motion', 'err_not_found')]);
        }


        $section = null;
        foreach ($motion->sections as $sec) {
            if ($sec->sectionId === IntVal($sectionId)) {
                $section = $sec;
            }
        }
        if (!$section) {
            return json_encode(['success' => false, 'error' => \Yii::t('motion', 'err_not_found')]);
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
        foreach ($section->getAmendingSections(true, false, true) as $sect) {
            $amendmentsById[$sect->amendmentId] = $sect->getAmendment();
        }

        $merger        = $section->getAmendmentDiffMerger($amendmentIds)->getParagraphMerger(IntVal($paragraphNo));
        $paragraphText = $merger->getFormattedDiffText($amendmentsById);
        $collisions    = [];

        $paragraphCollisions = $merger->getCollidingParagraphGroups();
        foreach ($paragraphCollisions as $amendmentId => $paraData) {
            $amendment    = $amendmentsById[$amendmentId];
            $collisions[] = $merger->getFormattedCollision($paraData, $amendment, $amendmentsById);
        }

        return json_encode([
            'text'       => $paragraphText,
            'collisions' => $collisions,
        ]);
    }

    public function actionMergeAmendmentsStatusAjax(string $motionSlug, string $knownAmendments): string
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return json_encode(['success' => false, 'error' => \Yii::t('motion', 'err_not_found')]);
        }

        $amendmentsById          = [];
        $newAmendmentsById       = [];
        $newAmendmentsStaticData = [];
        $newAmendmentsStatus     = [];

        $knownAmendments = array_map('intval', explode(',', $knownAmendments));
        foreach ($motion->getVisibleAmendments() as $amendment) {
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
                $type                               = $section->getSettings();
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

        return json_encode([
            'success' => true,
            'new'     => [
                'staticData' => $newAmendmentsStaticData,
                'status'     => $newAmendmentsStatus,
                'paragraphs' => $newAmendmentsParagraphs,
            ],
            'deleted' => $deletedAmendmentIds,
        ]);
    }

    /**
     * @param string $motionSlug
     *
     * @return string
     */
    public function actionMergeAmendmentsDraftPdf($motionSlug)
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

        $draft = $motion->getMergingDraft(false);
        if (!$draft) {
            return $this->showErrorpage(404, \Yii::t('motion', 'err_draft_not_found'));
        }

        $filename                    = $motion->getFilenameBase(false) . '-Merging-Draft.pdf';
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/pdf');
        \Yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $this->render('@app/views/merging/merging_draft_pdf', ['motion' => $motion, 'draft' => $draft]);
    }

    /**
     * @param string $motionSlug
     *
     * @return string
     */
    public function actionMergeAmendmentsInit($motionSlug)
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

        $amendments  = $motion->getVisibleAmendmentsSorted();
        $draft       = $motion->getMergingDraft(false);
        $unconfirmed = $motion->getMergingUnconfirmed();

        if (count($amendments) === 0 && !$draft && !$unconfirmed) {
            return $this->redirect(UrlHelper::createMotionUrl($motion, 'merge-amendments'));
        }

        return $this->render('@app/views/merging/init', [
            'motion'      => $motion,
            'amendments'  => $amendments,
            'draft'       => $draft,
            'unconfirmed' => $unconfirmed,
        ]);
    }

    private function getMotionForMerging($motionSlug)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));

            return null;
        }

        if (!$motion->canMergeAmendments()) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_edit_permission'));

            return null;
        }

        return $motion;
    }

    /**
     * @param string $motionSlug
     * @param string $activated
     *
     * @return string
     */
    public function actionMergeAmendmentsInitPdf($motionSlug, $activated = '')
    {
        $motion = $this->getMotionForMerging($motionSlug);
        if (!$motion) {
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        $amendments   = $motion->getVisibleAmendmentsSorted();
        $activatedIds = [];
        foreach (explode(',', $activated) as $active) {
            if ($active > 0) {
                $activatedIds[] = IntVal($active);
            }
        }

        $filename                    = $motion->getFilenameBase(false) . '-Merging-Selection.pdf';
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/pdf');
        \Yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $this->render('@app/views/merging/init_pdf', [
            'motion'     => $motion,
            'amendments' => $amendments,
            'activated'  => $activatedIds,
        ]);
    }

    /**
     * @param string $motionSlug
     *
     * @return string
     */
    public function actionMergeAmendmentsConfirm($motionSlug)
    {
        $newMotion = $this->consultation->getMotion($motionSlug);
        if (!$newMotion) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));

            return null;
        }

        $oldMotion = $newMotion->replacedMotion;
        if (!$oldMotion->canMergeAmendments()) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_edit_permission'));

            return null;
        }

        if ($this->isPostSet('modify')) {
            $merger = new Merge($oldMotion);
            $merger->updateDraftOnBackToModify(
                array_map('IntVal', \Yii::$app->request->post('amendStatus', [])),
                \Yii::$app->request->post('amendVotes', [])
            );

            return $this->redirect(UrlHelper::createMotionUrl($oldMotion, 'merge-amendments'));
        }

        if ($this->isPostSet('confirm')) {
            $merger = new Merge($oldMotion);
            $merger->confirm(
                $newMotion,
                array_map('IntVal', \Yii::$app->request->post('amendStatus', [])),
                \Yii::$app->request->post('newStatus'),
                \Yii::$app->request->post('newInitiator', ''),
                \Yii::$app->request->post('votes', ''),
                \Yii::$app->request->post('amendVotes', '')
            );

            return $this->render('@app/views/merging/done', [
                'newMotion' => $newMotion,
            ]);
        }

        try {
            $changes = MotionSectionChanges::motionToSectionChanges($oldMotion, $newMotion);
        } catch (Inconsistency $e) {
            $changes = [];
        }

        $mergingDraft = $oldMotion->getMergingDraft(false);

        return $this->render('@app/views/merging/confirm', [
            'newMotion'    => $newMotion,
            'mergingDraft' => $mergingDraft,
            'changes'      => $changes,
        ]);
    }

    /**
     * @param string $motionSlug
     *
     * @return string
     */
    public function actionMergeAmendments($motionSlug)
    {
        $motion = $this->getMotionForMerging($motionSlug);
        if (!$motion) {
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        $resumeDraft = $motion->getMergingDraft(false);

        try {
            if ($this->isPostSet('save')) {
                $public = ($resumeDraft ? $resumeDraft->public : false);
                $draft  = Draft::initFromJson($motion, $public, new \DateTime('now'), \Yii::$app->request->post('mergeDraft', null));
                $draft->save();

                $form      = new Merge($motion);
                $newMotion = $form->createNewMotion($draft);

                return $this->redirect(UrlHelper::createMotionUrl($newMotion, 'merge-amendments-confirm'));
            }
        } catch (\Exception $e) {
            \Yii::$app->session->setFlash('error', $e->getMessage());
        }

        if ($resumeDraft && !\Yii::$app->request->post('discard', 0) && count($resumeDraft->sections) === 1) {
            $form = Init::initFromDraft($motion, $resumeDraft);
        } else {
            $form = Init::fromInitForm($motion,
                \Yii::$app->request->post('amendments', []),
                \Yii::$app->request->post('textVersion', [])
            );
        }

        $twoCols = $motion->getMyMotionType()->getSettingsObj()->twoColMerging;

        return $this->render('@app/views/merging/merging', ['form' => $form, 'twoCols' => $twoCols]);
    }

    /**
     * @param $motionSlug
     *
     * @return string
     * @throws \Exception
     */
    public function actionSaveMergingDraft($motionSlug)
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return json_encode(['success' => false, 'error' => 'Motion not found']);
        }
        if (!$motion->canMergeAmendments()) {
            return json_encode(['success' => false, 'error' => 'Motion not editable']);
        }

        $public = (IntVal(\Yii::$app->request->post('public', 0)) === 1);
        $draft  = Draft::initFromJson($motion, $public, new \DateTime('now'), \Yii::$app->request->post('data', null));
        $draft->save();

        return json_encode([
            'success' => true,
            'date'    => ($draft->draftMotion->getDateTime() ? $draft->draftMotion->getDateTime()->format('c') : ''),
        ]);
    }
}
