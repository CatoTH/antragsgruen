<?php

namespace app\controllers\admin;

use app\components\MotionSorter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\ConsultationMotionType;
use app\models\db\Motion;
use app\models\exceptions\FormError;
use yii\web\Response;

class MotionController extends AdminBase
{
    use MotionListAllTrait;

    /**
     * @param ConsultationMotionType $motionType
     * @throws FormError
     */
    private function sectionsSave(ConsultationMotionType $motionType)
    {
        $position = 0;
        foreach ($_POST['sections'] as $sectionId => $data) {
            if (preg_match('/^new[0-9]+$/', $sectionId)) {
                $section               = new ConsultationSettingsMotionSection();
                $section->motionTypeId = $motionType->id;
                $section->type         = $data['type'];
                $section->status       = ConsultationSettingsMotionSection::STATUS_VISIBLE;
            } else {
                /** @var ConsultationSettingsMotionSection $section */
                $section = $motionType->getMotionSections()->andWhere('id = ' . IntVal($sectionId))->one();
                if (!$section) {
                    throw new FormError("Section not found: " . $sectionId);
                }
            }
            $section->setAdminAttributes($data);
            $section->position = $position;

            $section->save();

            $position++;
        }
    }

    /**
     * @param ConsultationMotionType $motionType
     * @throws FormError
     */
    private function sectionsDelete(ConsultationMotionType $motionType)
    {
        if (!isset($_POST['sectionsTodelete'])) {
            return;
        }
        foreach ($_POST['sectionsTodelete'] as $sectionId) {
            if ($sectionId > 0) {
                $sectionId = IntVal($sectionId);
                /** @var ConsultationSettingsMotionSection $section */
                $section = $motionType->getMotionSections()->andWhere('id = ' . $sectionId)->one();
                if (!$section) {
                    throw new FormError("Section not found: " . $sectionId);
                }
                $section->status = ConsultationSettingsMotionSection::STATUS_DELETED;
                $section->save();
            }
        }
    }

    /**
     * @param int $motionTypeId
     * @return string
     * @throws FormError
     */
    public function actionType($motionTypeId)
    {
        $motionType = $this->consultation->getMotionType($motionTypeId);
        if (isset($_POST['save'])) {
            $motionType->setAttributes($_POST['type']);
            $motionType->deadlineMotions    = Tools::dateBootstraptime2sql($_POST['type']['deadlineMotions']);
            $motionType->deadlineAmendments = Tools::dateBootstraptime2sql($_POST['type']['deadlineAmendments']);
            $form                           = $motionType->getMotionInitiatorFormClass();
            $form->setSettings($_POST['initiator']);
            $motionType->initiatorFormSettings = $form->getSettings();
            $motionType->save();

            $this->sectionsSave($motionType);
            $this->sectionsDelete($motionType);

            \yii::$app->session->setFlash('success', 'Gespeichert.');
            $motionType->refresh();
        }

        return $this->render('type', ['motionType' => $motionType]);
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $motions = $this->consultation->motions;
        return $this->render('index', ['motions' => $motions]);
    }

    /**
     * @param int $motionId
     * @return string
     */
    public function actionUpdate($motionId)
    {
        /** @var Motion $motion */
        $motion = $this->consultation->getMotion($motionId);
        if (!$motion) {
            $this->redirect(UrlHelper::createUrl('admin/motion/index'));
        }
        $this->checkConsistency($motion);

        if (isset($_POST['screen']) && $motion->status == Motion::STATUS_SUBMITTED_UNSCREENED) {
            $found = false;
            foreach ($this->consultation->motions as $motion) {
                if ($motion->titlePrefix == $_POST['titlePrefix'] && $motion->status != Motion::STATUS_DELETED) {
                    $found = true;
                }
            }
            if ($found) {
                \yii::$app->session->setFlash('error', 'Inzwischen gibt es einen anderen Antrag mit diesem Kürzel.');
            } else {
                $motion->status      = Motion::STATUS_SUBMITTED_SCREENED;
                $motion->titlePrefix = $_POST['titlePrefix'];
                $motion->save();
                $motion->onPublish();
                \yii::$app->session->setFlash('success', 'Der Antrag wurde freigeschaltet.');
            }
        }

        if (isset($_POST['save'])) {
            $modat                  = $_POST['motion'];
            $motion->title          = $modat['title'];
            $motion->statusString   = $modat['statusString'];
            $motion->dateCreation   = Tools::dateBootstraptime2sql($modat['dateCreation']);
            $motion->noteInternal   = $modat['noteInternal'];
            $motion->status         = $modat['status'];
            $motion->agendaItemId   = (isset($modat['agendaItemId']) ? $modat['agendaItemId'] : null);
            $motion->dateResolution = '';
            if ($modat['dateResolution'] != '') {
                $motion->dateResolution = Tools::dateBootstraptime2sql($modat['dateCreation']);
            }

            $foundPrefix = false;
            foreach ($this->consultation->motions as $mot) {
                if ($mot->titlePrefix != '' && $mot->id != $motion->id &&
                    $mot->titlePrefix == $modat['titlePrefix'] && $mot->status != Motion::STATUS_DELETED
                ) {
                    $foundPrefix = true;
                }
            }
            if ($foundPrefix) {
                $msg = "Das angegebene Antragskürzel wird bereits von einem anderen Antrag verwendet.";
                \yii::$app->session->setFlash('error', $msg);
            } else {
                $motion->titlePrefix = $_POST['motion']['titlePrefix'];
            }
            $motion->save();
            $motion->flushCaches();
            \yii::$app->session->setFlash('success', 'Gespeichert.');
        }

        return $this->render('update', ['motion' => $motion]);
    }

     /**
     * @param int $motionTypeId
     * @param bool $textCombined
     * @return string
     */
    public function actionExcellist($motionTypeId, $textCombined = false)
    {
        $motionType = $this->consultation->getMotionType($motionTypeId);

        @ini_set('memory_limit', '256M');

        $excelMime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', $excelMime);
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=motions.xlsx');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        $motions = MotionSorter::getSortedMotionsFlat($this->consultation, $motionType->motions);

        return $this->renderPartial('excel_list', [
            'motions'      => $motions,
            'textCombined' => $textCombined,
            'motionType'   => $motionType,
        ]);
    }
}
