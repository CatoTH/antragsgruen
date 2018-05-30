<?php

namespace app\plugins\memberPetitions\controllers;

use app\components\HTMLTools;
use app\components\UrlHelper;
use app\controllers\Base;
use app\plugins\memberPetitions\notifications\MotionResponded;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\db\MotionSupporter;
use app\models\db\User;
use app\models\exceptions\DB;
use app\plugins\memberPetitions\Tools;

class BackendController extends Base
{
    /**
     * @param string $motionSlug
     * @return string
     * @throws \yii\base\ExitException
     * @throws \app\models\exceptions\MailNotSent
     * @throws \app\models\exceptions\ServerConfiguration
     */
    public function actionWriteResponse($motionSlug)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));
            return $this->redirect(UrlHelper::createUrl('/consultation/index'));
        }

        if (!Tools::canRespondToPetition($motion)) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_edit_permission'));
            return $this->redirect(UrlHelper::createMotionUrl($motion));
        }

        try {
            $newMotion                 = new Motion();
            $newMotion->consultationId = $motion->consultationId;
            $newMotion->status         = Motion::STATUS_INLINE_REPLY;
            $newMotion->parentMotionId = $motion->id;
            $newMotion->motionTypeId   = $motion->motionTypeId;
            $newMotion->title          = $motion->title;
            $newMotion->titlePrefix    = $motion->titlePrefix . "-Reply";
            $newMotion->cache          = '';
            $newMotion->dateCreation   = date('Y-m-d H:i:s');

            if (!$newMotion->save()) {
                throw new DB($newMotion->getErrors());
            }

            $user                    = User::getCurrentUser();
            $supporter               = new MotionSupporter();
            $supporter->motionId     = $newMotion->id;
            $supporter->userId       = $user->id;
            $supporter->name         = \Yii::$app->request->post('responseFrom');
            $supporter->organization = '';
            $supporter->position     = 0;
            $supporter->role         = MotionSupporter::ROLE_INITIATOR;
            if (!$supporter->save()) {
                throw new DB($supporter->getErrors());
            }

            $postSections = \Yii::$app->request->post('sections', []);
            foreach ($motion->getActiveSections() as $section) {
                $newSection            = new MotionSection();
                $newSection->sectionId = $section->sectionId;
                $newSection->motionId  = $newMotion->id;
                $newSection->cache     = '';
                if (isset($postSections[$newSection->sectionId])) {
                    $forbidden           = $section->getSettings()->getForbiddenMotionFormattings();
                    $dataRaw             = $postSections[$newSection->sectionId];
                    $newSection->dataRaw = $dataRaw;
                    $newSection->data    = HTMLTools::cleanSimpleHtml($dataRaw, $forbidden);
                } else {
                    $newSection->dataRaw = $section->dataRaw;
                    $newSection->data    = $section->data;
                }
                if (!$newSection->save()) {
                    throw new DB($newSection->getErrors());
                }
            }
        } catch (\Exception $e) {
            \Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->redirect(UrlHelper::createMotionUrl($motion));
        }

        $motion->status = Motion::STATUS_PROCESSED;
        $motion->save();

        new MotionResponded($motion);

        \Yii::$app->session->setFlash('success', \Yii::t('memberPetitions', 'respond_saved'));
        return $this->redirect(UrlHelper::createMotionUrl($motion));
    }
}
