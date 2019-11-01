<?php

namespace app\controllers\admin;

use app\components\DateTools;
use app\components\HTMLTools;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\ConsultationMotionType;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use app\models\db\TexTemplate;
use app\models\db\User;
use app\models\exceptions\ExceptionBase;
use app\models\exceptions\FormError;
use app\models\forms\DeadlineForm;
use app\models\forms\MotionEditForm;
use app\models\forms\MotionMover;
use app\models\sectionTypes\ISectionType;
use app\models\settings\InitiatorForm;
use app\models\settings\MotionType;
use app\models\policies\IPolicy;
use app\models\motionTypeTemplates\Application as ApplicationTemplate;
use app\models\motionTypeTemplates\Motion as MotionTemplate;
use app\models\motionTypeTemplates\PDFApplication as PDFApplicationTemplate;
use app\models\events\MotionEvent;
use app\models\supportTypes\SupportBase;

class MotionController extends AdminBase
{
    public static $REQUIRED_PRIVILEGES = [
        User::PRIVILEGE_CONTENT_EDIT,
    ];

    /**
     * @param ConsultationMotionType $motionType
     *
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
                $section->type         = IntVal($data['type']);
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
     *
     * @return string
     * @throws FormError
     * @throws \app\models\exceptions\Internal
     * @throws \yii\base\ExitException
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

                return $this->render('type_deleted');
            } else {
                \Yii::$app->session->setFlash('error', \Yii::t('admin', 'motion_type_not_deletable'));
            }
        }
        if ($this->isPostSet('save')) {
            $input = \Yii::$app->request->post('type');
            $motionType->setAttributes($input);
            $motionType->amendmentMultipleParagraphs = (isset($input['amendSinglePara']) ? 0 : 1);
            $motionType->sidebarCreateButton         = (isset($input['sidebarCreateButton']) ? 1 : 0);

            $deadlineForm = DeadlineForm::createFromInput(\Yii::$app->request->post('deadlines'));
            $motionType->setAllDeadlines($deadlineForm->generateDeadlineArray());

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

            $settings                       = $motionType->getSettingsObj();
            $settings->pdfIntroduction      = $input['pdfIntroduction'];
            $settings->motionTitleIntro     = $input['typeMotionIntro'];
            $settings->hasProposedProcedure = isset($input['proposedProcedure']);
            $motionType->setSettingsObj($settings);

            $settings = $motionType->getMotionSupportTypeClass()->getSettingsObj();
            $settings->saveFormTyped(
                \Yii::$app->request->post('initiatorSettings', []),
                \Yii::$app->request->post('initiatorSettingFields', [])
            );
            $settings->initiatorCanBeOrganization = $this->isPostSet('initiatorCanBeOrganization');
            $settings->initiatorCanBePerson       = $this->isPostSet('initiatorCanBePerson');
            if (!$settings->initiatorCanBePerson && !$settings->initiatorCanBeOrganization) {
                // Probably a mistake
                $settings->initiatorCanBeOrganization = true;
                $settings->initiatorCanBePerson       = true;
            }
            $motionType->supportTypeSettings = json_encode($settings, JSON_PRETTY_PRINT);

            $motionType->save();

            $this->sectionsSave($motionType);
            $this->sectionsDelete($motionType);

            DateTools::setDeadlineDebugMode($this->consultation, $this->isPostSet('activateDeadlineDebugMode'));

            \yii::$app->session->setFlash('success', \Yii::t('admin', 'saved'));
            $motionType->refresh();
        }

        $supportCollPolicyWarning = false;
        if ($motionType->supportType === SupportBase::COLLECTING_SUPPORTERS) {
            if ($this->isPostSet('supportCollPolicyFix')) {
                if ($motionType->policyMotions === IPolicy::POLICY_ALL) {
                    $motionType->policyMotions = IPolicy::POLICY_LOGGED_IN;
                }
                $support = $motionType->policySupportMotions;
                if ($support === IPolicy::POLICY_ALL || $support === IPolicy::POLICY_NOBODY) {
                    $motionType->policySupportMotions = IPolicy::POLICY_LOGGED_IN;
                }
                if ($motionType->policyAmendments === IPolicy::POLICY_ALL) {
                    $motionType->policyAmendments = IPolicy::POLICY_LOGGED_IN;
                }
                $support = $motionType->policySupportAmendments;
                if ($support === IPolicy::POLICY_ALL || $support === IPolicy::POLICY_NOBODY) {
                    $motionType->policySupportAmendments = IPolicy::POLICY_LOGGED_IN;
                }
                $motionType->motionLikesDislikes    |= SupportBase::LIKEDISLIKE_SUPPORT;
                $motionType->amendmentLikesDislikes |= SupportBase::LIKEDISLIKE_SUPPORT;
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
            $createMotion  = ($motionType->policyMotions === IPolicy::POLICY_ALL);
            $createAmend   = ($motionType->policyAmendments === IPolicy::POLICY_ALL);
            $supportMotion = ($supportMotion === IPolicy::POLICY_ALL || $supportMotion === IPolicy::POLICY_NOBODY);
            $supportAmend  = ($supportAmend === IPolicy::POLICY_ALL || $supportAmend === IPolicy::POLICY_NOBODY);
            $noOffMotion   = (($motionType->motionLikesDislikes & SupportBase::LIKEDISLIKE_SUPPORT) === 0);
            $noOffAmend    = (($motionType->amendmentLikesDislikes & SupportBase::LIKEDISLIKE_SUPPORT) === 0);
            $noEmail       = !$this->consultation->getSettings()->initiatorConfirmEmails;

            $supportCollPolicyWarning = (
                $createMotion || $createAmend || $supportMotion || $supportAmend || $noEmail ||
                $noOffMotion || $noOffAmend
            );
        }

        if ($this->isRequestSet('msg') && $this->getRequestValue('msg') === 'created') {
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'motion_type_created_msg'));
        }

        return $this->render('type', [
            'motionType'               => $motionType,
            'supportCollPolicyWarning' => $supportCollPolicyWarning
        ]);
    }

    /**
     * @return string
     * @throws \yii\base\ExitException
     * @throws \Exception
     */
    public function actionTypecreate()
    {
        if ($this->isPostSet('create')) {
            $type         = \Yii::$app->request->post('type');
            $sectionsFrom = null;
            if (isset($type['preset']) && $type['preset'] === 'application') {
                $motionType = ApplicationTemplate::doCreateApplicationType($this->consultation);
                ApplicationTemplate::doCreateApplicationSections($motionType);
            } elseif (isset($type['preset']) && $type['preset'] === 'motion') {
                $motionType = MotionTemplate::doCreateMotionType($this->consultation);
                MotionTemplate::doCreateMotionSections($motionType);
            } elseif (isset($type['preset']) && $type['preset'] === 'pdfapplication') {
                $motionType = PDFApplicationTemplate::doCreateApplicationType($this->consultation);
                PDFApplicationTemplate::doCreateApplicationSections($motionType);
            } else {
                $motionType = null;
                foreach ($this->consultation->motionTypes as $cType) {
                    if (is_numeric($type['preset']) && $cType->id === IntVal($type['preset'])) {
                        $motionType = new ConsultationMotionType();
                        $motionType->setAttributes($cType->getAttributes(), false);
                        $motionType->id = null;
                        $sectionsFrom   = $cType;
                    }
                }
                if (!$motionType) {
                    $motionType                               = new ConsultationMotionType();
                    $motionType->consultationId               = $this->consultation->id;
                    $motionType->policyMotions                = IPolicy::POLICY_ALL;
                    $motionType->policyAmendments             = IPolicy::POLICY_ALL;
                    $motionType->policyComments               = IPolicy::POLICY_NOBODY;
                    $motionType->policySupportMotions         = IPolicy::POLICY_ALL;
                    $motionType->policySupportAmendments      = IPolicy::POLICY_ALL;
                    $motionType->initiatorsCanMergeAmendments = ConsultationMotionType::INITIATORS_MERGE_NEVER;
                    $motionType->motionLikesDislikes          = 0;
                    $motionType->amendmentLikesDislikes       = 0;
                    $motionType->amendmentMultipleParagraphs  = 1;
                    $motionType->position                     = 0;
                    $motionType->supportType                  = SupportBase::ONLY_INITIATOR;
                    $motionType->status                       = 0;
                    $motionType->sidebarCreateButton          = 1;

                    $initiatorSettings               = new InitiatorForm(null);
                    $initiatorSettings->contactName  = InitiatorForm::CONTACT_NONE;
                    $initiatorSettings->contactPhone = InitiatorForm::CONTACT_OPTIONAL;
                    $initiatorSettings->contactEmail = InitiatorForm::CONTACT_OPTIONAL;
                    $motionType->supportTypeSettings = json_encode($initiatorSettings, JSON_PRETTY_PRINT);

                    $motionType->setSettingsObj(new MotionType(null));

                    $texTemplates              = TexTemplate::find()->all();
                    $motionType->texTemplateId = (count($texTemplates) > 0 ? $texTemplates[0]->id : null);
                }
            }
            $motionType->titleSingular = $type['titleSingular'];
            $motionType->titlePlural   = $type['titlePlural'];
            $motionType->createTitle   = $type['createTitle'];
            $motionType->motionPrefix  = $type['motionPrefix'];

            if (strpos($type['pdfLayout'], 'php') === 0) {
                $motionType->pdfLayout     = IntVal(str_replace('php', '', $type['pdfLayout']));
                $motionType->texTemplateId = null;
            } elseif ($type['pdfLayout']) {
                $motionType->texTemplateId = IntVal($type['pdfLayout']);
            }

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

            $url = UrlHelper::createUrl(['/admin/motion/type', 'motionTypeId' => $motionType->id, 'msg' => 'created']);

            return $this->redirect($url);
        }

        return $this->render('type_create');
    }

    /**
     * @param Motion $motion
     *
     * @throws \Throwable
     */
    private function saveMotionSupporters(Motion $motion)
    {
        $names         = \Yii::$app->request->post('supporterName', []);
        $orgas         = \Yii::$app->request->post('supporterOrga', []);
        $genders       = \Yii::$app->request->post('supporterGender', []);
        $preIds        = \Yii::$app->request->post('supporterId', []);
        $newSupporters = [];
        /** @var MotionSupporter[] $preSupporters */
        $preSupporters = [];
        foreach ($motion->getSupporters() as $supporter) {
            $preSupporters[$supporter->id] = $supporter;
        }
        for ($i = 0; $i < count($names); $i++) {
            if (trim($names[$i]) === '' && trim($orgas[$i]) === '') {
                continue;
            }
            if (isset($preSupporters[$preIds[$i]])) {
                $supporter = $preSupporters[$preIds[$i]];
            } else {
                $supporter               = new MotionSupporter();
                $supporter->motionId     = $motion->id;
                $supporter->role         = MotionSupporter::ROLE_SUPPORTER;
                $supporter->personType   = MotionSupporter::PERSON_NATURAL;
                $supporter->dateCreation = date('Y-m-d H:i:s');
            }
            $supporter->name         = $names[$i];
            $supporter->organization = $orgas[$i];
            $supporter->position     = $i;
            $supporter->setExtraDataEntry('gender', (isset($genders[$i]) ? $genders[$i] : null));
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
     *
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionGetAmendmentRewriteCollisions($motionId)
    {
        $newSections = \Yii::$app->request->post('newSections', []);

        /** @var Motion $motion */
        $motion     = $this->consultation->getMotion($motionId);
        $collisions = $amendments = [];
        foreach ($motion->getAmendmentsRelevantForCollisionDetection() as $amendment) {
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                $coll = $section->getRewriteCollisions($newSections[$section->sectionId], false);
                if (count($coll) > 0) {
                    if (!in_array($amendment, $amendments, true)) {
                        $amendments[$amendment->id] = $amendment;
                        $collisions[$amendment->id] = [];
                    }
                    $collisions[$amendment->id][$section->sectionId] = $coll;
                }
            }
        }

        return $this->renderPartial('@app/views/amendment/ajax_rewrite_collisions', [
            'amendments' => $amendments,
            'collisions' => $collisions,
        ]);
    }

    /**
     * @param int $motionId
     *
     * @return string
     * @throws \Exception
     * @throws \Throwable
     * @throws \app\models\exceptions\Internal
     * @throws \yii\base\ExitException
     * @throws \yii\db\StaleObjectException
     */
    public function actionUpdate($motionId)
    {
        /** @var Motion $motion */
        $motion = $this->consultation->getMotion($motionId);
        if (!$motion) {
            $this->redirect(UrlHelper::createUrl('admin/motion-list/index'));
        }
        $this->checkConsistency($motion);

        $this->layout = 'column2';
        $post         = \Yii::$app->request->post();

        $form = new MotionEditForm($motion->motionType, $motion->agendaItem, $motion);
        $form->setAdminMode(true);

        if ($this->isPostSet('screen') && $motion->isInScreeningProcess()) {
            if ($this->consultation->findMotionWithPrefix($post['titlePrefix'], $motion)) {
                \yii::$app->session->setFlash('error', \Yii::t('admin', 'motion_prefix_collision'));
            } else {
                $motion->status      = Motion::STATUS_SUBMITTED_SCREENED;
                $motion->titlePrefix = $post['titlePrefix'];
                $motion->save();
                $motion->trigger(Motion::EVENT_PUBLISHED, new MotionEvent($motion));
                \yii::$app->session->setFlash('success', \Yii::t('admin', 'motion_screened'));
            }
        }

        if ($this->isPostSet('delete')) {
            $motion->status = Motion::STATUS_DELETED;
            $motion->save();
            $motion->flushCacheStart();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'motion_deleted'));
            $this->redirect(UrlHelper::createUrl('admin/motion-list/index'));

            return '';
        }

        if ($this->isPostSet('save')) {
            $modat = $post['motion'];

            $sectionTypes = [];
            foreach ($motion->getActiveSections() as $section) {
                $sectionTypes[$section->sectionId] = $section->getSettings()->type;
            }

            try {
                $form->setAttributes([$post, $_FILES]);

                $votingData = $motion->getVotingData();
                $votingData->setFromPostData($post['votes']);
                $motion->setVotingData($votingData);

                $form->saveMotion($motion);
                if (isset($post['sections'])) {
                    $overrides = (isset($post['amendmentOverride']) ? $post['amendmentOverride'] : []);
                    $newHtmls  = [];
                    foreach ($post['sections'] as $sectionId => $html) {
                        $htmlTypes = [ISectionType::TYPE_TEXT_SIMPLE, ISectionType::TYPE_TEXT_HTML];
                        if (isset($sectionTypes[$sectionId]) && in_array($sectionTypes[$sectionId], $htmlTypes)) {
                            $newHtmls[$sectionId] = HTMLTools::cleanSimpleHtml($html);
                        }
                    }
                    $form->updateTextRewritingAmendments($motion, $newHtmls, $overrides);
                }
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }

            if (IntVal($modat['motionType']) !== $motion->motionTypeId) {
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

            $motion->title        = $modat['title'];
            $motion->statusString = $modat['statusString'];
            $motion->noteInternal = $modat['noteInternal'];
            $motion->status       = IntVal($modat['status']);
            $motion->agendaItemId = (isset($modat['agendaItemId']) ? IntVal($modat['agendaItemId']) : null);
            $motion->nonAmendable = (isset($modat['nonAmendable']) ? 1 : 0);

            $roundedDate = Tools::dateBootstraptime2sql($modat['dateCreation']);
            if (substr($roundedDate, 0, 16) !== substr($motion->dateCreation, 0, 16)) {
                $motion->dateCreation = $roundedDate;
            }

            if ($modat['dateResolution'] !== '') {
                $roundedDate = Tools::dateBootstraptime2sql($modat['dateResolution']);
                if (substr($roundedDate, 0, 16) !== substr($motion->dateResolution, 0, 16)) {
                    $motion->dateResolution = $roundedDate;
                }
            } else {
                $motion->dateResolution = null;
            }

            if ($modat['datePublication'] !== '') {
                $roundedDate = Tools::dateBootstraptime2sql($modat['datePublication']);
                if (substr($roundedDate, 0, 16) !== substr($motion->datePublication, 0, 16)) {
                    $motion->datePublication = $roundedDate;
                }
            } else {
                $motion->datePublication = null;
            }

            if ($modat['parentMotionId'] && IntVal($modat['parentMotionId']) !== $motion->id &&
                $this->consultation->getMotion($modat['parentMotionId'])) {
                $motion->parentMotionId = IntVal($modat['parentMotionId']);
            } else {
                $motion->parentMotionId = null;
            }

            if ($this->consultation->findMotionWithPrefix($modat['titlePrefix'], $motion)) {
                \yii::$app->session->setFlash('error', \Yii::t('admin', 'motion_prefix_collision'));
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

    public function actionMove($motionId)
    {
        /** @var Motion $motion */
        $motion = $this->consultation->getMotion($motionId);
        if (!$motion) {
            $this->redirect(UrlHelper::createUrl('admin/motion-list/index'));
        }
        $this->checkConsistency($motion);


        if ($this->isPostSet('move')) {
            $form      = new MotionMover($this->consultation, $motion);
            $newMotion = $form->move(\Yii::$app->request->post());
            if ($newMotion) {
                return $this->redirect(UrlHelper::createMotionUrl($newMotion));
            }
        }

        return $this->render('move', ['motion' => $motion]);
    }
}
