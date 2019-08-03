<?php

namespace app\controllers;

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use app\models\events\MotionEvent;
use app\models\exceptions\Inconsistency;
use app\models\exceptions\Internal;
use app\models\forms\MotionMergeAmendmentsDraftForm;
use app\models\forms\MotionMergeAmendmentsForm;
use app\models\forms\MotionMergeAmendmentsInitForm;
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
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');
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
            'date'    => ($draft->getDateTime() ? $draft->getDateTime()->format('c') : ''),
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
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

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
                $amendmentIds[] = $this->consultation->getAmendment($amendment['id'])->proposalReference->id;
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
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');
        \yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');

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

    /**
     * @param string $motionSlug
     * @param string $activated
     *
     * @return string
     */
    public function actionMergeAmendmentsInitPdf($motionSlug, $activated = '')
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

        $amendments   = $motion->getVisibleAmendmentsSorted();
        $activatedIds = [];
        foreach (explode(',', $activated) as $active) {
            if ($active > 0) {
                $activatedIds[] = IntVal($active);
            }
        }

        $filename                    = $motion->getFilenameBase(false) . '-Merging-Selection.pdf';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/pdf');
        \yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        \yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $this->render('@app/views/merging/init_pdf', [
            'motion'     => $motion,
            'amendments' => $amendments,
            'activated'  => $activatedIds,
        ]);
    }

    /**
     * @param string $motionSlug
     * @param string $amendmentStatuses
     *
     * @return string
     */
    public function actionMergeAmendmentsConfirm($motionSlug, $amendmentStatuses = '')
    {
        $newMotion = $this->consultation->getMotion($motionSlug);
        if (!$newMotion || $newMotion->status !== Motion::STATUS_DRAFT || !$newMotion->replacedMotion) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));

            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }
        $oldMotion     = $newMotion->replacedMotion;
        $amendStatuses = ($amendmentStatuses === '' ? [] : json_decode($amendmentStatuses, true));

        if ($this->isPostSet('modify')) {
            return $this->redirect(UrlHelper::createMotionUrl($oldMotion, 'merge-amendments', [
                'newMotionId'       => $newMotion->id,
                'amendmentStatuses' => $amendmentStatuses
            ]));
        }

        if ($this->isPostSet('confirm')) {
            $invisible = $this->consultation->getInvisibleAmendmentStatuses();
            foreach ($oldMotion->getVisibleAmendments() as $amendment) {
                if (isset($amendStatuses[$amendment->id])) {
                    $newStatus = IntVal($amendStatuses[$amendment->id]);
                    if ($newStatus !== $amendment->status && !in_array($amendStatuses[$amendment->id], $invisible)) {
                        $amendment->status = $newStatus;
                        $amendment->save();
                    }
                }
            }

            if ($newMotion->replacedMotion->slug) {
                $newMotion->slug                 = $newMotion->replacedMotion->slug;
                $newMotion->replacedMotion->slug = null;
                $newMotion->replacedMotion->save();
            }

            $isResolution = false;
            if ($newMotion->canCreateResolution()) {
                $resolutionMode = \Yii::$app->request->post('newStatus');
                if ($resolutionMode === 'resolution_final') {
                    $newMotion->status = IMotion::STATUS_RESOLUTION_FINAL;
                    $isResolution      = true;
                } elseif ($resolutionMode === 'resolution_preliminary') {
                    $newMotion->status = IMotion::STATUS_RESOLUTION_PRELIMINARY;
                    $isResolution      = true;
                } else {
                    $newMotion->status = $newMotion->replacedMotion->status;
                }
            } else {
                $newMotion->status = $newMotion->replacedMotion->status;
            }
            if ($isResolution) {
                $resolutionDate            = \Yii::$app->request->post('dateResolution', '');
                $resolutionDate            = Tools::dateBootstrapdate2sql($resolutionDate);
                $newMotion->dateResolution = ($resolutionDate ? $resolutionDate : null);
            }
            $newMotion->save();

            // For resolutions, the state of the original motion should not be changed
            if (!$isResolution && $newMotion->replacedMotion->status === Motion::STATUS_SUBMITTED_SCREENED) {
                $newMotion->replacedMotion->status = Motion::STATUS_MODIFIED;
                $newMotion->replacedMotion->save();
            }

            if ($isResolution) {
                $resolutionBody = \Yii::$app->request->post('newInitiator', '');
                if (trim($resolutionBody) !== '') {
                    $body                 = new MotionSupporter();
                    $body->motionId       = $newMotion->id;
                    $body->position       = 0;
                    $body->dateCreation   = date('Y-m-d H:i:s');
                    $body->personType     = MotionSupporter::PERSON_ORGANIZATION;
                    $body->role           = MotionSupporter::ROLE_INITIATOR;
                    $body->organization   = $resolutionBody;
                    $resolutionDate       = \Yii::$app->request->post('dateResolution', '');
                    $resolutionDate       = Tools::dateBootstrapdate2sql($resolutionDate);
                    $body->resolutionDate = ($resolutionDate ? $resolutionDate : null);
                    if (!$body->save()) {
                        var_dump($body->getErrors());
                        die();
                    }
                }
            }

            $mergingDraft = $oldMotion->getMergingDraft(false);
            if ($mergingDraft) {
                $mergingDraft->status = IMotion::STATUS_DELETED;
                $mergingDraft->save();
            }

            // If the old motion was the only / forced motion of the consultation, set the new one as the forced one.
            if ($this->consultation->getSettings()->forceMotion === $oldMotion->id) {
                $settings              = $this->consultation->getSettings();
                $settings->forceMotion = $newMotion->id;
                $this->consultation->setSettings($settings);
                $this->consultation->save();
            }

            $newMotion->trigger(Motion::EVENT_MERGED, new MotionEvent($newMotion));

            return $this->render('@app/views/merging/done', [
                'newMotion' => $newMotion,
            ]);
        }

        try {
            $changes = MotionSectionChanges::motionToSectionChanges($oldMotion, $newMotion);
        } catch (Inconsistency $e) {
            $changes = [];
        }

        return $this->render('@app/views/merging/confirm', [
            'newMotion'         => $newMotion,
            'amendmentStatuses' => $amendStatuses,
            'changes'           => $changes,
        ]);
    }

    /**
     * @param string $motionSlug
     *
     * @return string
     */
    public function actionMergeAmendments($motionSlug)
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

        try {
            if ($this->isPostSet('save')) {
                $form = new MotionMergeAmendmentsForm($motion);
                $newMotion = $form->createNewMotion(\Yii::$app->request->post());

                return $this->redirect(UrlHelper::createMotionUrl($newMotion, 'merge-amendments-confirm', [
                    'fromMode'          => 'create',
                    'amendmentStatuses' => $form->encodeAmendmentStatuses(\Yii::$app->request->post()),
                ]));
            }
        } catch (\Exception $e) {
            \yii::$app->session->setFlash('error', $e->getMessage());
        }

        $resumeDraft = $motion->getMergingDraft(false);
        if ($resumeDraft && !\Yii::$app->request->post('discard', 0) && count($resumeDraft->sections) === 1) {
            $form = MotionMergeAmendmentsInitForm::initFromDraft($motion, $resumeDraft);
        } else {
            $form = MotionMergeAmendmentsInitForm::fromInitForm(
                $motion,
                \Yii::$app->request->post('amendments', []),
                \Yii::$app->request->post('textVersion', [])
            );
        }

        return $this->render('@app/views/merging/merging', ['form' => $form,]);
    }

    /**
     * @param $motionSlug
     *
     * @return string
     */
    public function actionSaveMergingDraft($motionSlug)
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return json_encode(['success' => false, 'error' => 'Motion not found']);
        }
        if (!$motion->canMergeAmendments()) {
            return json_encode(['success' => false, 'error' => 'Motion not editable']);
        }

        $form  = new MotionMergeAmendmentsDraftForm($motion);
        $draft = $form->save(
            \Yii::$app->request->post('public', 0),
            \Yii::$app->request->post('data', null)
        );

        return json_encode([
            'success' => true,
            'date'    => ($draft->getDateTime() ? $draft->getDateTime()->format('c') : ''),
        ]);
    }
}
