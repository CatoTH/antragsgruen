<?php

namespace app\controllers\admin;

use app\components\HTMLTools;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\ConsultationMotionType;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use app\models\db\TexTemplate;
use app\models\exceptions\ExceptionBase;
use app\models\exceptions\FormError;
use app\models\forms\MotionEditForm;
use app\models\sectionTypes\ISectionType;
use app\models\settings\AntragsgruenApp;
use app\models\supportTypes\ISupportType;
use app\models\policies\IPolicy;
use app\components\motionTypeTemplates\Application as ApplicationTemplate;
use app\components\motionTypeTemplates\Motion as MotionTemplate;
use app\views\motion\LayoutHelper;
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
        if (!\Yii::$app->request->post('sections')) {
            return;
        }
        foreach (\Yii::$app->request->post('sections') as $sectionId => $data) {
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
        if (!$this->isPostSet('sectionsTodelete')) {
            return;
        }
        foreach (\Yii::$app->request->post('sectionsTodelete') as $sectionId) {
            if ($sectionId > 0) {
                $sectionId = IntVal($sectionId);
                /** @var ConsultationSettingsMotionSection $section */
                $section = $motionType->getMotionSections()->andWhere('id = ' . $sectionId)->one();
                if ($section) {
                    $section->status = ConsultationSettingsMotionSection::STATUS_DELETED;
                    $section->save();
                }
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
        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }
        if ($this->isPostSet('delete')) {
            if ($motionType->isDeletable()) {
                $motionType->status = ConsultationMotionType::STATUS_DELETED;
                $motionType->save();
                return $this->render('type_deleted', []);
            } else {
                \Yii::$app->session->setFlash('error', \Yii::t('admin', 'motion_type_not_deletable'));
            }
        }
        if ($this->isPostSet('save')) {
            $input = \Yii::$app->request->post('type');
            $motionType->setAttributes($input);
            $motionType->deadlineMotions             = Tools::dateBootstraptime2sql($input['deadlineMotions']);
            $motionType->deadlineAmendments          = Tools::dateBootstraptime2sql($input['deadlineAmendments']);
            $motionType->amendmentMultipleParagraphs = (isset($input['amendSinglePara']) ? 0 : 1);

            $pdfTemplate = \Yii::$app->request->post('pdfTemplate');
            if (strpos($pdfTemplate, 'php') === 0) {
                $motionType->pdfLayout     = IntVal(str_replace('php', '', $pdfTemplate));
                $motionType->texTemplateId = null;
            } elseif ($pdfTemplate) {
                $motionType->texTemplateId = IntVal($pdfTemplate);
            }

            $motionType->motionLikesDislikes = 0;
            if (isset($input['motionLikesDislikes'])) {
                foreach ($input['motionLikesDislikes'] as $val) {
                    $motionType->motionLikesDislikes += $val;
                }
            }
            $motionType->amendmentLikesDislikes = 0;
            if (isset($input['amendmentLikesDislikes'])) {
                foreach ($input['amendmentLikesDislikes'] as $val) {
                    $motionType->amendmentLikesDislikes += $val;
                }
            }

            $form = $motionType->getMotionSupportTypeClass();
            $form->setSettings(\Yii::$app->request->post('initiator'));
            $motionType->supportTypeSettings = $form->getSettings();
            $motionType->save();

            $this->sectionsSave($motionType);
            $this->sectionsDelete($motionType);

            \yii::$app->session->setFlash('success', \Yii::t('admin', 'saved'));
            $motionType->refresh();
        }

        $supportCollPolicyWarning = false;
        if ($motionType->supportType == ISupportType::COLLECTING_SUPPORTERS) {
            if ($this->isPostSet('supportCollPolicyFix')) {
                if ($motionType->policyMotions == IPolicy::POLICY_ALL) {
                    $motionType->policyMotions = IPolicy::POLICY_LOGGED_IN;
                }
                $support = $motionType->policySupportMotions;
                if ($support == IPolicy::POLICY_ALL || $support == IPolicy::POLICY_NOBODY) {
                    $motionType->policySupportMotions = IPolicy::POLICY_LOGGED_IN;
                }
                if ($motionType->policyAmendments == IPolicy::POLICY_ALL) {
                    $motionType->policyAmendments = IPolicy::POLICY_LOGGED_IN;
                }
                $support = $motionType->policySupportAmendments;
                if ($support == IPolicy::POLICY_ALL || $support == IPolicy::POLICY_NOBODY) {
                    $motionType->policySupportAmendments = IPolicy::POLICY_LOGGED_IN;
                }
                $motionType->motionLikesDislikes    |= ISupportType::LIKEDISLIKE_SUPPORT;
                $motionType->amendmentLikesDislikes |= ISupportType::LIKEDISLIKE_SUPPORT;
                $motionType->save();
                if (!$this->consultation->getSettings()->initiatorConfirmEmails) {
                    $settings                         = $this->consultation->getSettings();
                    $settings->initiatorConfirmEmails = true;
                    $this->consultation->setSettings($settings);
                    $this->consultation->save();
                }
            }

            $supportMotion = $motionType->policySupportMotions;
            $supportAmend  = $motionType->policySupportAmendments;
            $createMotion  = ($motionType->policyMotions == IPolicy::POLICY_ALL);
            $createAmend   = ($motionType->policyAmendments == IPolicy::POLICY_ALL);
            $supportMotion = ($supportMotion == IPolicy::POLICY_ALL || $supportMotion == IPolicy::POLICY_NOBODY);
            $supportAmend  = ($supportAmend == IPolicy::POLICY_ALL || $supportAmend == IPolicy::POLICY_NOBODY);
            $noOffMotion   = (($motionType->motionLikesDislikes & ISupportType::LIKEDISLIKE_SUPPORT) == 0);
            $noOffAmend    = (($motionType->amendmentLikesDislikes & ISupportType::LIKEDISLIKE_SUPPORT) == 0);
            $noEmail       = !$this->consultation->getSettings()->initiatorConfirmEmails;

            $supportCollPolicyWarning = (
                $createMotion || $createAmend || $supportMotion || $supportAmend || $noEmail ||
                $noOffMotion || $noOffAmend
            );
        }

        if ($this->isRequestSet('msg') && $this->getRequestValue('msg') == 'created') {
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'motion_type_created_msg'));
        }

        return $this->render('type', [
            'motionType'               => $motionType,
            'supportCollPolicyWarning' => $supportCollPolicyWarning
        ]);
    }

    /**
     * @return string
     */
    public function actionTypecreate()
    {
        if ($this->isPostSet('create')) {
            $type         = \Yii::$app->request->post('type');
            $sectionsFrom = null;
            if (isset($type['preset']) && $type['preset'] == 'application') {
                $motionType = ApplicationTemplate::doCreateApplicationType($this->consultation);
                ApplicationTemplate::doCreateApplicationSections($motionType);
            } elseif (isset($type['preset']) && $type['preset'] == 'motion') {
                $motionType = MotionTemplate::doCreateMotionType($this->consultation);
                MotionTemplate::doCreateMotionSections($motionType);
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
                    $motionType                               = new ConsultationMotionType();
                    $motionType->consultationId               = $this->consultation->id;
                    $motionType->layoutTwoCols                = 0;
                    $motionType->policyMotions                = IPolicy::POLICY_ALL;
                    $motionType->policyAmendments             = IPolicy::POLICY_ALL;
                    $motionType->policyComments               = IPolicy::POLICY_NOBODY;
                    $motionType->policySupportMotions         = IPolicy::POLICY_ALL;
                    $motionType->policySupportAmendments      = IPolicy::POLICY_ALL;
                    $motionType->initiatorsCanMergeAmendments = ConsultationMotionType::INITIATORS_MERGE_NEVER;
                    $motionType->motionLikesDislikes          = 0;
                    $motionType->amendmentLikesDislikes       = 0;
                    $motionType->contactName                  = ConsultationMotionType::CONTACT_NONE;
                    $motionType->contactEmail                 = ConsultationMotionType::CONTACT_OPTIONAL;
                    $motionType->contactPhone                 = ConsultationMotionType::CONTACT_OPTIONAL;
                    $motionType->amendmentMultipleParagraphs  = 1;
                    $motionType->position                     = 0;
                    $motionType->supportType                  = ISupportType::ONLY_INITIATOR;
                    $motionType->status                       = 0;

                    $texTemplates              = TexTemplate::find()->all();
                    $motionType->texTemplateId = (count($texTemplates) > 0 ? $texTemplates[0]->id : null);
                }
            }
            $motionType->titleSingular = $type['titleSingular'];
            $motionType->titlePlural   = $type['titlePlural'];
            $motionType->createTitle   = $type['createTitle'];
            $motionType->pdfLayout     = $type['pdfLayout'];
            $motionType->motionPrefix  = $type['motionPrefix'];
            if (!$motionType->save()) {
                var_dump($motionType->getErrors());
                die();
            }

            if ($sectionsFrom) {
                foreach ($sectionsFrom->motionSections as $cSection) {
                    $motionSection = new ConsultationSettingsMotionSection();
                    $motionSection->setAttributes($cSection->getAttributes(), false);
                    $motionSection->id           = null;
                    $motionSection->motionTypeId = $motionType->id;
                    $motionSection->save();
                }
            }

            $url = UrlHelper::createUrl(['admin/motion/type', 'motionTypeId' => $motionType->id, 'msg' => 'created']);
            return $this->redirect($url);
        }
        return $this->render('type_create', []);
    }

    /**
     * @param Motion $motion
     */
    private function saveMotionSupporters(Motion $motion)
    {
        $names         = \Yii::$app->request->post('supporterName', []);
        $orgas         = \Yii::$app->request->post('supporterOrga', []);
        $preIds        = \Yii::$app->request->post('supporterId', []);
        $newSupporters = [];
        /** @var MotionSupporter[] $preSupporters */
        $preSupporters = [];
        foreach ($motion->getSupporters() as $supporter) {
            $preSupporters[$supporter->id] = $supporter;
        }
        for ($i = 0; $i < count($names); $i++) {
            if (trim($names[$i]) == '' && trim($orgas[$i]) == '') {
                continue;
            }
            if (isset($preSupporters[$preIds[$i]])) {
                $supporter = $preSupporters[$preIds[$i]];
            } else {
                $supporter             = new MotionSupporter();
                $supporter->motionId   = $motion->id;
                $supporter->role       = MotionSupporter::ROLE_SUPPORTER;
                $supporter->personType = MotionSupporter::PERSON_NATURAL;
            }
            $supporter->name         = $names[$i];
            $supporter->organization = $orgas[$i];
            $supporter->position     = $i;
            if (!$supporter->save()) {
                var_dump($supporter->getErrors());
                die();
            }
            $newSupporters[$supporter->id] = $supporter;
        }

        foreach ($preSupporters as $supporter) {
            if (!isset($newSupporters[$supporter->id])) {
                $supporter->delete();
            }
        }

        $motion->refresh();
    }

    /**
     * @param int $motionId
     * @return string
     */
    public function actionGetAmendmentRewriteCollissions($motionId)
    {
        $newSections = \Yii::$app->request->post('newSections', []);

        /** @var Motion $motion */
        $motion      = $this->consultation->getMotion($motionId);
        $collissions = $amendments = [];
        foreach ($motion->getAmendmentsRelevantForCollissionDetection() as $amendment) {
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                $coll = $section->getRewriteCollissions($newSections[$section->sectionId], false);
                if (count($coll) > 0) {
                    if (!in_array($amendment, $amendments)) {
                        $amendments[$amendment->id]  = $amendment;
                        $collissions[$amendment->id] = [];
                    }
                    $collissions[$amendment->id][$section->sectionId] = $coll;
                }
            }
        }
        return $this->renderPartial('@app/views/amendment/ajax_rewrite_collissions', [
            'amendments'  => $amendments,
            'collissions' => $collissions,
        ]);
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
        $post         = \Yii::$app->request->post();

        $form = new MotionEditForm($motion->motionType, $motion->agendaItem, $motion);
        $form->setAdminMode(true);

        if ($this->isPostSet('screen') && $motion->isInScreeningProcess()) {
            if ($this->consultation->findMotionWithPrefix($post['titlePrefix'], $motion)) {
                \yii::$app->session->setFlash('error', \Yii::t('admin', 'motion_prefix_collission'));
            } else {
                $motion->status      = Motion::STATUS_SUBMITTED_SCREENED;
                $motion->titlePrefix = $post['titlePrefix'];
                $motion->save();
                $motion->onPublish();
                \yii::$app->session->setFlash('success', \Yii::t('admin', 'motion_screened'));
            }
        }

        if ($this->isPostSet('delete')) {
            $motion->status = Motion::STATUS_DELETED;
            $motion->save();
            $motion->flushCacheStart();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'motion_deleted'));
            $this->redirect(UrlHelper::createUrl('admin/motion/listall'));
            return '';
        }

        if ($this->isPostSet('save')) {
            $modat = $post['motion'];

            try {
                $form->setAttributes([$post, $_FILES]);
                $form->saveMotion($motion);
                if (isset($post['sections'])) {
                    $overrides = (isset($post['amendmentOverride']) ? $post['amendmentOverride'] : []);
                    $newHtmls  = [];
                    foreach ($post['sections'] as $sectionId => $html) {
                        $newHtmls[$sectionId] = HTMLTools::cleanSimpleHtml($html);
                    }
                    $form->updateTextRewritingAmendments($motion, $newHtmls, $overrides);
                }
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }

            if ($modat['motionType'] != $motion->motionTypeId) {
                try {
                    /** @var ConsultationMotionType $newType */
                    $newType = ConsultationMotionType::findOne($modat['motionType']);
                    if (!$newType || $newType->consultationId != $motion->consultationId) {
                        throw new FormError('The new motion type was not found');
                    }
                    $motion->setMotionType($newType);
                } catch (FormError $e) {
                    \Yii::$app->session->setFlash('error', $e->getMessage());
                }
            }

            $motion->title          = $modat['title'];
            $motion->statusString   = $modat['statusString'];
            $motion->dateCreation   = Tools::dateBootstraptime2sql($modat['dateCreation']);
            $motion->noteInternal   = $modat['noteInternal'];
            $motion->status         = $modat['status'];
            $motion->agendaItemId   = (isset($modat['agendaItemId']) ? $modat['agendaItemId'] : null);
            $motion->nonAmendable   = (isset($modat['nonAmendable']) ? 1 : 0);
            $motion->dateResolution = '';
            if ($modat['dateResolution'] != '') {
                $motion->dateResolution = Tools::dateBootstraptime2sql($modat['dateResolution']);
            }

            if ($this->consultation->findMotionWithPrefix($modat['titlePrefix'], $motion)) {
                \yii::$app->session->setFlash('error', \Yii::t('admin', 'motion_prefix_collission'));
            } else {
                $motion->titlePrefix = $post['motion']['titlePrefix'];
            }
            $motion->save();

            foreach ($this->consultation->tags as $tag) {
                if (!$this->isPostSet('tags') || !in_array($tag->id, $post['tags'])) {
                    $motion->unlink('tags', $tag);
                } else {
                    try {
                        $motion->link('tags', $tag);
                    } catch (\Exception $e) {
                    }
                }
            }

            $this->saveMotionSupporters($motion);

            $motion->flushCacheWithChildren();
            \yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
        }

        return $this->render('update', ['motion' => $motion, 'form' => $form]);
    }

    /**
     * @param int $motionTypeId
     * @param bool $textCombined
     * @param int $withdrawn
     * @return string
     */
    public function actionOdslist($motionTypeId, $textCombined = false, $withdrawn = 0)
    {
        $withdrawn = ($withdrawn == 1);

        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=motions.ods');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        $motions = [];
        foreach ($this->consultation->getVisibleMotionsSorted($withdrawn) as $motion) {
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
     * @param int $withdrawn
     * @return string
     */
    public function actionExcellist($motionTypeId, $textCombined = false, $withdrawn = 0)
    {
        if (!AntragsgruenApp::hasPhpExcel()) {
            return $this->showErrorpage(500, 'The Excel package has not been installed. ' .
                'To install it, execute "./composer.phar require phpoffice/phpexcel".');
        }

        $withdrawn = ($withdrawn == 1);

        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }

        $excelMime                   = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', $excelMime);
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=motions.xlsx');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        error_reporting(E_ALL & ~E_DEPRECATED); // PHPExcel ./. PHP 7

        $motions = [];
        foreach ($this->consultation->getVisibleMotionsSorted($withdrawn) as $motion) {
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

    /**
     * @param int $motionTypeId
     * @param int $version
     * @return string
     */
    public function actionOpenslides($motionTypeId, $version = 1)
    {
        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }

        $filename                    = rawurlencode($motionType->titlePlural);
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'text/csv');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=' . $filename . '.csv');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        $motions = [];
        foreach ($this->consultation->getVisibleMotionsSorted(false) as $motion) {
            if ($motion->motionTypeId == $motionTypeId) {
                $motions[] = $motion;
            }
        }

        if ($version == 1) {
            return $this->renderPartial('openslides1_list', [
                'motions' => $motions,
            ]);
        } else {
            return $this->renderPartial('openslides2_list', [
                'motions' => $motions,
            ]);
        }
    }

    /**
     * @param int $motionTypeId
     * @param int $withdrawn
     * @return string
     */
    public function actionPdfziplist($motionTypeId = 0, $withdrawn = 0)
    {
        $withdrawn = ($withdrawn == 1);

        try {
            if ($motionTypeId > 0) {
                $motions = $this->consultation->getMotionType($motionTypeId)->getVisibleMotions($withdrawn);
            } else {
                $motions = $this->consultation->getVisibleMotions($withdrawn);
            }
            if (count($motions) == 0) {
                return $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
            }
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }

        $zip = new \app\components\ZipWriter();
        foreach ($motions as $motion) {
            $zip->addFile($motion->getFilenameBase(false) . '.pdf', LayoutHelper::createPdf($motion));
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/zip');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=motions_pdf.zip');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $zip->getContentAndFlush();
    }

    /**
     * @param int $motionTypeId
     * @param int $withdrawn
     * @return string
     */
    public function actionOdtziplist($motionTypeId = 0, $withdrawn = 0)
    {
        $withdrawn = ($withdrawn == 1);

        try {
            if ($motionTypeId > 0) {
                $motions = $this->consultation->getMotionType($motionTypeId)->getVisibleMotions($withdrawn);
            } else {
                $motions = $this->consultation->getVisibleMotions($withdrawn);
            }
            if (count($motions) == 0) {
                return $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
            }
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }

        $zip = new \app\components\ZipWriter();
        foreach ($motions as $motion) {
            $zip->addFile($motion->getFilenameBase(false) . '.odt', LayoutHelper::createOdt($motion));
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/zip');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=motions_odt.zip');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $zip->getContentAndFlush();
    }
}
