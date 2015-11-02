<?php

namespace app\controllers\admin;

use app\components\MotionSorter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\ConsultationMotionType;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\exceptions\FormError;
use app\models\forms\MotionEditForm;
use app\models\sitePresets\ApplicationTrait;
use app\models\sitePresets\MotionTrait;
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
                    throw new FormError('Section not found: ' . $sectionId);
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
                    throw new FormError('Section not found: ' . $sectionId);
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
            $input = $_POST['type'];
            $motionType->setAttributes($input);
            $motionType->deadlineMotions             = Tools::dateBootstraptime2sql($input['deadlineMotions']);
            $motionType->deadlineAmendments          = Tools::dateBootstraptime2sql($input['deadlineAmendments']);
            $motionType->amendmentMultipleParagraphs = (isset($input['amendSinglePara']) ? 0 : 1);
            $form                                    = $motionType->getMotionInitiatorFormClass();
            $form->setSettings($_POST['initiator']);
            $motionType->initiatorFormSettings = $form->getSettings();
            $motionType->save();

            $this->sectionsSave($motionType);
            $this->sectionsDelete($motionType);

            \yii::$app->session->setFlash('success', \Yii::t('admin', 'saved'));
            $motionType->refresh();
        }

        return $this->render('type', ['motionType' => $motionType]);
    }

    /**
     * @return string
     */
    public function actionTypecreate()
    {
        if (isset($_POST['create'])) {
            $type         = $_POST['type'];
            $sectionsFrom = null;
            if (isset($type['preset']) && $type['preset'] == 'application') {
                $motionType = ApplicationTrait::doCreateApplicationType($this->consultation);
                ApplicationTrait::doCreateApplicationSections($motionType);
            } elseif (isset($type['preset']) && $type['preset'] == 'motion') {
                $motionType = MotionTrait::doCreateMotionType($this->consultation);
                MotionTrait::doCreateMotionSections($motionType);
            } else {
                $motionType = null;
                foreach ($this->consultation->motionTypes as $cType) {
                    if ($cType->id == $type['preset']) {
                        $motionType = new ConsultationMotionType();
                        $motionType->setAttributes($cType->getAttributes(), false);
                        $motionType->id = null;
                        $sectionsFrom   = $cType;
                    }
                }
                if (!$motionType) {
                    $motionType                 = new ConsultationMotionType();
                    $motionType->consultationId = $this->consultation->id;
                }
            }
            $motionType->titleSingular = $type['titleSingular'];
            $motionType->titlePlural   = $type['titlePlural'];
            $motionType->createTitle   = $type['createTitle'];
            $motionType->pdfLayout     = $type['pdfLayout'];
            $motionType->motionPrefix  = $type['motionPrefix'];
            $motionType->save();

            if ($sectionsFrom) {
                foreach ($sectionsFrom->motionSections as $cSection) {
                    $motionSection = new ConsultationSettingsMotionSection();
                    $motionSection->setAttributes($cSection->getAttributes(), false);
                    $motionSection->id           = null;
                    $motionSection->motionTypeId = $motionType->id;
                    $motionSection->save();
                }
            }

            return $this->redirect(UrlHelper::createUrl(['admin/motion/type', 'motionTypeId' => $motionType->id]));
        }
        return $this->render('type_create', []);
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
            $this->redirect(UrlHelper::createUrl('admin/motion/listall'));
        }
        $this->checkConsistency($motion);

        $this->layout = 'column2';

        $form = new MotionEditForm($motion->motionType, $motion->agendaItem, $motion);
        $form->setAdminMode(true);

        if (isset($_POST['screen']) && $motion->status == Motion::STATUS_SUBMITTED_UNSCREENED) {
            if ($this->consultation->findMotionWithPrefix($_POST['titlePrefix'], $motion)) {
                \yii::$app->session->setFlash('error', \Yii::t('admin', 'motion_prefix_collission'));
            } else {
                $motion->status      = Motion::STATUS_SUBMITTED_SCREENED;
                $motion->titlePrefix = $_POST['titlePrefix'];
                $motion->save();
                $motion->onPublish();
                \yii::$app->session->setFlash('success', \Yii::t('admin', 'motion_screened'));
            }
        }

        if (isset($_POST['delete'])) {
            $motion->status = Motion::STATUS_DELETED;
            $motion->save();
            $motion->flushCacheStart();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'motion_deleted'));
            $this->redirect(UrlHelper::createUrl('admin/motion/listall'));
            return '';
        }

        if (isset($_POST['save'])) {
            $form->setAttributes([$_POST, $_FILES]);
            try {
                $form->saveMotion($motion);
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }

            $modat                  = $_POST['motion'];
            $motion->title          = $modat['title'];
            $motion->statusString   = $modat['statusString'];
            $motion->dateCreation   = Tools::dateBootstraptime2sql($modat['dateCreation']);
            $motion->noteInternal   = $modat['noteInternal'];
            $motion->status         = $modat['status'];
            $motion->agendaItemId   = (isset($modat['agendaItemId']) ? $modat['agendaItemId'] : null);
            $motion->dateResolution = '';
            if ($modat['dateResolution'] != '') {
                $motion->dateResolution = Tools::dateBootstraptime2sql($modat['dateResolution']);
            }

            if ($this->consultation->findMotionWithPrefix($modat['titlePrefix'], $motion)) {
                \yii::$app->session->setFlash('error', \Yii::t('admin', 'motion_prefix_collission'));
            } else {
                $motion->titlePrefix = $_POST['motion']['titlePrefix'];
            }
            $motion->save();

            foreach ($this->consultation->tags as $tag) {
                if (!isset($_POST['tags']) || !in_array($tag->id, $_POST['tags'])) {
                    $motion->unlink('tags', $tag);
                } else {
                    try {
                        $motion->link('tags', $tag);
                    } catch (\Exception $e) {
                    }
                }
            }

            $motion->flushCacheWithChildren();
            \yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
        }

        return $this->render('update', ['motion' => $motion, 'form' => $form]);
    }

    /**
     * @param int $motionTypeId
     * @param bool $textCombined
     * @return string
     * @throws \app\models\exceptions\NotFound
     */
    public function actionOdslist($motionTypeId, $textCombined = false)
    {
        $motionType = $this->consultation->getMotionType($motionTypeId);

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=motions.ods');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        $motions = [];
        foreach ($this->consultation->getVisibleMotionsSorted() as $motion) {
            if ($motion->motionTypeId == $motionTypeId) {
                $motions[] = $motion;
            }
        }

        return $this->renderPartial('ods_list', [
            'motions'      => $motions,
            'textCombined' => $textCombined,
            'motionType'   => $motionType,
        ]);
    }

    /**
     * @param int $motionTypeId
     * @param bool $textCombined
     * @return string
     */
    public function actionExcellist($motionTypeId, $textCombined = false)
    {
        $motionType = $this->consultation->getMotionType($motionTypeId);

        $excelMime                   = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', $excelMime);
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=motions.xlsx');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        $motions = [];
        foreach ($this->consultation->getVisibleMotionsSorted() as $motion) {
            if ($motion->motionTypeId == $motionTypeId) {
                $motions[] = $motion;
            }
        }

        return $this->renderPartial('excel_list', [
            'motions'      => $motions,
            'textCombined' => $textCombined,
            'motionType'   => $motionType,
        ]);
    }
}
