<?php

namespace app\controllers;

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\events\MotionEvent;
use app\models\exceptions\FormError;
use app\models\exceptions\Inconsistency;
use app\models\exceptions\Internal;
use app\models\forms\MotionMergeAmendmentsDraftForm;
use app\models\forms\MotionMergeAmendmentsForm;
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

        return $this->render('merge_amendments_public', ['motion' => $motion, 'draft' => $draft]);
    }

    /**
     * @param string $motionSlug
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
            'html'    => $this->renderPartial('_merge_amendments_public', ['motion' => $motion, 'draft' => $draft]),
            'date'    => Tools::formatMysqlDateTime($draft->dateCreation),
        ]);
    }

    /**
     * @param string $motionSlug
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

        return $this->render('merge_amendments_init', [
            'motion'      => $motion,
            'amendments'  => $amendments,
            'draft'       => $draft,
            'unconfirmed' => $unconfirmed,
        ]);
    }

    /**
     * @param string $motionSlug
     * @param string $amendmentStatuses
     * @return string
     */
    public function actionMergeAmendmentsConfirm($motionSlug, $amendmentStatuses = '')
    {
        $newMotion = $this->consultation->getMotion($motionSlug);
        if (!$newMotion || $newMotion->status != Motion::STATUS_DRAFT || !$newMotion->replacedMotion) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }
        $oldMotion     = $newMotion->replacedMotion;
        $amendStatuses = ($amendmentStatuses == '' ? [] : json_decode($amendmentStatuses, true));

        if ($this->isPostSet('modify')) {
            return $this->redirect(UrlHelper::createMotionUrl($oldMotion, 'merge-amendments', [
                'newMotionId'       => $newMotion->id,
                'amendmentStatuses' => $amendmentStatuses
            ]));
        }

        if ($this->isPostSet('confirm')) {
            $invisible = $this->consultation->getInvisibleAmendmentStatuses();
            foreach ($oldMotion->getVisibleAmendments() as $amendment) {
                if (isset($amendStatuses[$amendment->id]) && $amendStatuses[$amendment->id] != $amendment->status) {
                    if (!in_array($amendStatuses[$amendment->id], $invisible)) {
                        $amendment->status = $amendStatuses[$amendment->id];
                        $amendment->save();
                    }
                }
            }

            if ($newMotion->replacedMotion->slug) {
                $newMotion->slug                 = $newMotion->replacedMotion->slug;
                $newMotion->replacedMotion->slug = null;
                $newMotion->replacedMotion->save();
            }
            $newMotion->status = $newMotion->replacedMotion->status;
            $newMotion->save();

            if ($newMotion->replacedMotion->status == Motion::STATUS_SUBMITTED_SCREENED) {
                $newMotion->replacedMotion->status = Motion::STATUS_MODIFIED;
                $newMotion->replacedMotion->save();
            }

            $mergingDraft = $oldMotion->getMergingDraft(false);
            if ($mergingDraft) {
                $mergingDraft->status = IMotion::STATUS_DELETED;
                $mergingDraft->save();
            }

            $newMotion->trigger(Motion::EVENT_MERGED, new MotionEvent($newMotion));

            return $this->render('merge_amendments_done', [
                'newMotion' => $newMotion,
            ]);
        }

        try {
            $changes = MotionSectionChanges::motionToSectionChanges($oldMotion, $newMotion);
        } catch (Inconsistency $e) {
            $changes = [];
        }

        $draftId = null;
        return $this->render('merge_amendments_confirm', [
            'newMotion'         => $newMotion,
            'deleteDraftId'     => $draftId,
            'amendmentStatuses' => $amendStatuses,
            'changes'           => $changes,
        ]);
    }

    /**
     * @param string $motionSlug
     * @param int $newMotionId
     * @param string $amendmentStatuses
     * @return string
     */
    public function actionMergeAmendments($motionSlug, $newMotionId = 0, $amendmentStatuses = '')
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

        $form = new MotionMergeAmendmentsForm($motion, $newMotion);

        try {
            if ($this->isPostSet('save')) {
                $form->setAttributes(\Yii::$app->request->post());
                try {
                    $newMotion = $form->createNewMotion();
                    return $this->redirect(UrlHelper::createMotionUrl($newMotion, 'merge-amendments-confirm', [
                        'fromMode'          => 'create',
                        'amendmentStatuses' => json_encode($form->amendStatus),
                        'draftId'           => $this->getRequestValue('draftId'),
                    ]));
                } catch (FormError $e) {
                    \Yii::$app->session->setFlash('error', $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            \yii::$app->session->setFlash('error', $e->getMessage());
        }

        $amendStatuses = ($amendmentStatuses == '' ? [] : json_decode($amendmentStatuses, true));

        $resumeDraft = $motion->getMergingDraft(false);
        if ($resumeDraft && \Yii::$app->request->post('discard', 0) == 1) {
            $resumeDraft = null;
        }

        $toMergeAmendmentIds = [];
        $postAmendIds        = \Yii::$app->request->post('amendments', []);
        $textVersions        = \Yii::$app->request->post('textVersion', []);
        foreach ($motion->getVisibleAmendments() as $amendment) {
            if (isset($postAmendIds[$amendment->id])) {
                if (isset($textVersions[$amendment->id]) && $textVersions[$amendment->id] == 'proposal') {
                    $toMergeAmendmentIds[] = $amendment->proposalReference->id;
                } else {
                    $toMergeAmendmentIds[] = $amendment->id;
                }
            }
        }

        return $this->render('merge_amendments', [
            'motion'              => $motion,
            'form'                => $form,
            'amendmentStatuses'   => $amendStatuses,
            'resumeDraft'         => $resumeDraft,
            'toMergeAmendmentIds' => $toMergeAmendmentIds,
        ]);
    }

    /**
     * @param $motionSlug
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
            \Yii::$app->request->post('sections', [])
        );

        return json_encode(['success' => true, 'date' => $draft->dateCreation]);
    }
}
