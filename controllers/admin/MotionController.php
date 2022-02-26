<?php

namespace app\controllers\admin;

use app\models\consultationLog\ProposedProcedureChange;
use app\models\policies\{All, Nobody, UserGroups, IPolicy};
use app\components\{DateTools, HTMLTools, Tools, UrlHelper};
use app\models\db\{Consultation,
    ConsultationLog,
    ConsultationSettingsMotionSection,
    ConsultationMotionType,
    ConsultationSettingsTag,
    ConsultationUserGroup,
    Motion,
    MotionSupporter,
    TexTemplate,
    User};
use app\models\exceptions\{ExceptionBase, FormError};
use app\models\events\MotionEvent;
use app\models\forms\{DeadlineForm, MotionEditForm, MotionMover};
use app\models\motionTypeTemplates\{
    Application as ApplicationTemplate,
    Motion as MotionTemplate,
    PDFApplication as PDFApplicationTemplate,
    Statutes as StatutesTemplate
};
use app\models\sectionTypes\ISectionType;
use app\models\settings\{AntragsgruenApp, InitiatorForm, MotionSection, MotionType, Site};
use app\models\supportTypes\SupportBase;
use yii\web\Response;

class MotionController extends AdminBase
{
    public static $REQUIRED_PRIVILEGES = [
        ConsultationUserGroup::PRIVILEGE_CONTENT_EDIT,
    ];

    /**
     * @throws FormError
     */
    private function sectionsSave(ConsultationMotionType $motionType): void
    {
        $position = 0;
        if (!\Yii::$app->request->post('sections')) {
            return;
        }
        foreach (\Yii::$app->request->post('sections') as $sectionId => $data) {
            if (preg_match('/^new[0-9]+$/', $sectionId)) {
                $section               = new ConsultationSettingsMotionSection();
                $section->motionTypeId = $motionType->id;
                $section->type         = intval($data['type']);
                $section->status       = ConsultationSettingsMotionSection::STATUS_VISIBLE;

                $settings = $section->getSettingsObj();
                $settings->public = (isset($data['nonPublic']) ? MotionSection::PUBLIC_NO : MotionSection::PUBLIC_YES);
                $section->setSettingsObj($settings);
            } else {
                /** @var ConsultationSettingsMotionSection $section */
                $section = $motionType->getMotionSections()->andWhere('id = ' . intval($sectionId))->one();
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

    private function sectionsDelete(ConsultationMotionType $motionType): void
    {
        if (!$this->isPostSet('sectionsTodelete')) {
            return;
        }
        foreach (\Yii::$app->request->post('sectionsTodelete') as $sectionId) {
            if ($sectionId > 0) {
                $sectionId = intval($sectionId);
                /** @var ConsultationSettingsMotionSection $section */
                $section = $motionType->getMotionSections()->andWhere('id = ' . $sectionId)->one();
                if ($section) {
                    $section->status = ConsultationSettingsMotionSection::STATUS_DELETED;
                    $section->save();
                }
            }
        }
    }

    private function getPolicyFromUpdateData(ConsultationMotionType $motionType, array $data): IPolicy
    {
        $consultation = $motionType->getConsultation();
        $policy = IPolicy::getInstanceFromDb($data['id'], $consultation, $motionType);
        if (is_a($policy, UserGroups::class)) {
            $groups = array_filter($consultation->getAllAvailableUserGroups(), function(ConsultationUserGroup $group) use ($data): bool {
                return in_array($group->id, $data['groups'] ?? []);
            });
            $policy->setAllowedUserGroups($groups);
        }
        return $policy;
    }

    public function actionType(string $motionTypeId): string
    {
        $motionTypeId = intval($motionTypeId);

        if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CONSULTATION_SETTINGS)) {
            $this->showErrorpage(403, \Yii::t('admin', 'no_access'));
            return '';
        }

        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            $this->showErrorpage(404, $e->getMessage());
            return '';
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
            if (isset($input['typeAmendSingleChange'])) {
                $motionType->amendmentMultipleParagraphs = ConsultationMotionType::AMEND_PARAGRAPHS_SINGLE_CHANGE;
            } elseif (isset($input['amendSinglePara'])) {
                $motionType->amendmentMultipleParagraphs = ConsultationMotionType::AMEND_PARAGRAPHS_SINGLE_PARAGRAPH;
            } else {
                $motionType->amendmentMultipleParagraphs = ConsultationMotionType::AMEND_PARAGRAPHS_MULTIPLE;
            }
            $motionType->sidebarCreateButton         = (isset($input['sidebarCreateButton']) ? 1 : 0);
            $motionType->setMotionPolicy($this->getPolicyFromUpdateData($motionType, $input['policyMotions']));
            $motionType->setMotionSupportPolicy($this->getPolicyFromUpdateData($motionType, $input['policySupportMotions']));
            $motionType->setAmendmentPolicy($this->getPolicyFromUpdateData($motionType, $input['policyAmendments']));
            $motionType->setAmendmentSupportPolicy($this->getPolicyFromUpdateData($motionType, $input['policySupportAmendments']));
            $motionType->setCommentPolicy($this->getPolicyFromUpdateData($motionType, $input['policyComments']));

            $deadlineForm = DeadlineForm::createFromInput(\Yii::$app->request->post('deadlines'));
            $motionType->setAllDeadlines($deadlineForm->generateDeadlineArray());

            $pdfTemplate = \Yii::$app->request->post('pdfTemplate', '');
            if (strpos($pdfTemplate, 'php') === 0) {
                $motionType->pdfLayout     = intval(str_replace('php', '', $pdfTemplate));
                $motionType->texTemplateId = null;
            } elseif ($pdfTemplate) {
                $motionType->texTemplateId = intval($pdfTemplate);
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
            $settings->hasResponsibilities  = isset($input['responsibilities']);
            $motionType->setSettingsObj($settings);

            // Motion Initiators / Supporters
            $settings = $motionType->getMotionSupportTypeClass()->getSettingsObj();
            $settings->saveFormTyped(
                \Yii::$app->request->post('motionInitiatorSettings', []),
                \Yii::$app->request->post('motionInitiatorSettingFields', [])
            );
            $settings->initiatorCanBeOrganization = $this->isPostSet('initiatorCanBeOrganization');
            $settings->initiatorCanBePerson       = $this->isPostSet('initiatorCanBePerson');
            if (!$settings->initiatorCanBePerson && !$settings->initiatorCanBeOrganization) {
                // Probably a mistake
                $settings->initiatorCanBeOrganization = true;
                $settings->initiatorCanBePerson       = true;
            }
            $motionType->supportTypeMotions = json_encode($settings, JSON_PRETTY_PRINT);

            if ($this->isPostSet('sameInitiatorSettingsForAmendments')) {
                $motionType->supportTypeAmendments = null;
            } else {
                // Amendment Initiators / Supporters
                $settings = $motionType->getAmendmentSupportTypeClass()->getSettingsObj();
                $settings->saveFormTyped(
                    \Yii::$app->request->post('amendmentInitiatorSettings', []),
                    \Yii::$app->request->post('amendmentInitiatorSettingFields', [])
                );
                $settings->initiatorCanBeOrganization = $this->isPostSet('amendmentInitiatorCanBeOrganization');
                $settings->initiatorCanBePerson       = $this->isPostSet('amendmentInitiatorCanBePerson');
                if (!$settings->initiatorCanBePerson && !$settings->initiatorCanBeOrganization) {
                    // Probably a mistake
                    $settings->initiatorCanBeOrganization = true;
                    $settings->initiatorCanBePerson       = true;
                }
                $motionType->supportTypeAmendments = json_encode($settings, JSON_PRETTY_PRINT);
            }

            $motionType->save();

            $this->sectionsSave($motionType);
            $this->sectionsDelete($motionType);

            DateTools::setDeadlineDebugMode($this->consultation, $this->isPostSet('activateDeadlineDebugMode'));

            \Yii::$app->session->setFlash('success', \Yii::t('admin', 'saved'));
            $motionType->refresh();

            foreach ($this->consultation->getMotionsOfType($motionType) as $motion) {
                $motion->flushCacheStart(null);
            }
        }

        $supportCollPolicyWarning = false;
        if ($motionType->getMotionSupporterSettings()->type === SupportBase::COLLECTING_SUPPORTERS) {
            if ($this->isPostSet('supportCollPolicyFix')) {
                if (is_a($motionType->getMotionPolicy(), All::class)) {
                    $motionType->policyMotions = (string)IPolicy::POLICY_LOGGED_IN;
                }
                if (is_a($motionType->getMotionSupportPolicy(), All::class) || is_a($motionType->getMotionSupportPolicy(), Nobody::class)) {
                    $motionType->policySupportMotions = (string)IPolicy::POLICY_LOGGED_IN;
                }
                if (is_a($motionType->getAmendmentPolicy(), All::class)) {
                    $motionType->policyAmendments = (string)IPolicy::POLICY_LOGGED_IN;
                }
                if (is_a($motionType->getAmendmentSupportPolicy(), All::class) || is_a($motionType->getAmendmentSupportPolicy(), Nobody::class)) {
                    $motionType->policySupportAmendments = (string)IPolicy::POLICY_LOGGED_IN;
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

            $createMotion  = (is_a($motionType->getMotionPolicy(), All::class));
            $createAmend   = (is_a($motionType->getAmendmentPolicy(), All::class));
            $supportMotion = (is_a($motionType->getMotionSupportPolicy(), All::class) || is_a($motionType->getMotionSupportPolicy(), Nobody::class));
            $supportAmend  = (is_a($motionType->getAmendmentSupportPolicy(), All::class) || is_a($motionType->getAmendmentSupportPolicy(), Nobody::class));
            $noOffMotion   = (($motionType->motionLikesDislikes & SupportBase::LIKEDISLIKE_SUPPORT) === 0);
            $noOffAmend    = (($motionType->amendmentLikesDislikes & SupportBase::LIKEDISLIKE_SUPPORT) === 0);
            $noEmail       = !$this->consultation->getSettings()->initiatorConfirmEmails;

            $supportCollPolicyWarning = (
                $createMotion || $createAmend || $supportMotion || $supportAmend || $noEmail ||
                $noOffMotion || $noOffAmend
            );
        }

        if ($this->isRequestSet('msg') && $this->getRequestValue('msg') === 'created') {
            \Yii::$app->session->setFlash('success', \Yii::t('admin', 'motion_type_created_msg'));
        }

        return $this->render('type', [
            'motionType'               => $motionType,
            'supportCollPolicyWarning' => $supportCollPolicyWarning
        ]);
    }

    /**
     * @return string
     * @throws \Yii\base\ExitException
     * @throws \Exception
     */
    public function actionTypecreate()
    {
        if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CONSULTATION_SETTINGS)) {
            $this->showErrorpage(403, \Yii::t('admin', 'no_access'));

            return false;
        }

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
            } elseif (isset($type['preset']) && $type['preset'] === 'statute') {
                $motionType = StatutesTemplate::doCreateStatutesType($this->consultation);
                StatutesTemplate::doCreateStatutesSections($motionType);
            } else {
                $motionType = null;
                foreach ($this->consultation->motionTypes as $cType) {
                    if (is_numeric($type['preset']) && $cType->id === intval($type['preset'])) {
                        $motionType = new ConsultationMotionType();
                        $motionType->setAttributes($cType->getAttributes(), false);
                        $motionType->id = null;
                        $sectionsFrom   = $cType;
                    }
                }
                if (!$motionType) {
                    $motionType                               = new ConsultationMotionType();
                    $motionType->consultationId               = $this->consultation->id;
                    $motionType->policyMotions                = (string)IPolicy::POLICY_ALL;
                    $motionType->policyAmendments             = (string)IPolicy::POLICY_ALL;
                    $motionType->policyComments               = (string)IPolicy::POLICY_NOBODY;
                    $motionType->policySupportMotions         = (string)IPolicy::POLICY_ALL;
                    $motionType->policySupportAmendments      = (string)IPolicy::POLICY_ALL;
                    $motionType->initiatorsCanMergeAmendments = ConsultationMotionType::INITIATORS_MERGE_NEVER;
                    $motionType->motionLikesDislikes          = 0;
                    $motionType->amendmentLikesDislikes       = 0;
                    $motionType->amendmentMultipleParagraphs  = ConsultationMotionType::AMEND_PARAGRAPHS_MULTIPLE;
                    $motionType->amendmentsOnly               = 0;
                    $motionType->position                     = 0;
                    $motionType->status                       = 0;
                    $motionType->sidebarCreateButton          = 1;

                    $initiatorSettings                 = new InitiatorForm(null);
                    $initiatorSettings->type           = SupportBase::ONLY_INITIATOR;
                    $initiatorSettings->contactName    = InitiatorForm::CONTACT_NONE;
                    $initiatorSettings->contactPhone   = InitiatorForm::CONTACT_OPTIONAL;
                    $initiatorSettings->contactEmail   = InitiatorForm::CONTACT_OPTIONAL;
                    $motionType->supportTypeMotions    = json_encode($initiatorSettings, JSON_PRETTY_PRINT);
                    $motionType->supportTypeAmendments = null;

                    $motionType->setSettingsObj(new MotionType(null));

                    $texTemplates              = TexTemplate::find()->all();
                    $motionType->texTemplateId = (count($texTemplates) > 0 ? $texTemplates[0]->id : null);
                }
            }
            $motionType->titleSingular = $type['titleSingular'];
            $motionType->titlePlural   = $type['titlePlural'];
            $motionType->createTitle   = $type['createTitle'];
            $motionType->motionPrefix  = substr($type['motionPrefix'], 0, 10);

            if (strpos($type['pdfLayout'], 'php') === 0) {
                $motionType->pdfLayout     = intval(str_replace('php', '', $type['pdfLayout']));
                $motionType->texTemplateId = null;
            } elseif ($type['pdfLayout']) {
                $motionType->texTemplateId = intval($type['pdfLayout']);
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
     * @throws \Throwable
     */
    private function saveMotionSupporters(Motion $motion): void
    {
        $names         = \Yii::$app->request->post('supporterName', []);
        $orgas         = \Yii::$app->request->post('supporterOrga', []);
        $genders       = \Yii::$app->request->post('supporterGender', []);
        $preIds        = \Yii::$app->request->post('supporterId', []);
        $newSupporters = [];
        /** @var MotionSupporter[] $preSupporters */
        $preSupporters = [];
        foreach ($motion->getSupporters(true) as $supporter) {
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

    private function saveMotionInitiator(Motion $motion): void
    {
        if (\Yii::$app->request->post('initiatorSet') !== '1') {
            return;
        }
        $setType = \Yii::$app->request->post('initiatorSetType');
        $setUsername = \Yii::$app->request->post('initiatorSetUsername');

        switch ($setType) {
            case 'email':
                $user = User::findByAuthTypeAndName(Site::LOGIN_STD, $setUsername);
                break;
            case 'gruenesnetz':
                $user = User::findByAuthTypeAndName(Site::LOGIN_GRUENES_NETZ, $setUsername);
                break;
            default:
                $user = null;
        }

        if ($setUsername && !$user) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_user_not_found'));
            return;
        }

        foreach ($motion->getInitiators() as $initiator) {
            $initiator->userId = ($user ? $user->id : null);
            $initiator->save();
            $initiator->refresh();
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
     * @return string
     * @throws \Throwable
     * @throws \app\models\exceptions\Internal
     */
    public function actionUpdate(string $motionId)
    {
        /** @var Motion $motion */
        $motion = $this->consultation->getMotion($motionId);
        if (!$motion) {
            $this->redirect(UrlHelper::createUrl('admin/motion-list/index'));
        }
        $this->checkConsistency($motion);

        $this->layout = 'column2';
        $post         = \Yii::$app->request->post();

        if ($this->isPostSet('screen') && $motion->isInScreeningProcess()) {
            if ($this->consultation->findMotionWithPrefix($post['titlePrefix'], $motion)) {
                \Yii::$app->session->setFlash('error', \Yii::t('admin', 'motion_prefix_collision'));
            } else {
                $motion->status      = Motion::STATUS_SUBMITTED_SCREENED;
                $motion->titlePrefix = $post['titlePrefix'];
                $motion->save();
                $motion->trigger(Motion::EVENT_PUBLISHED, new MotionEvent($motion));
                \Yii::$app->session->setFlash('success', \Yii::t('admin', 'motion_screened'));
            }
        }

        if ($this->isPostSet('delete')) {
            $motion->setDeleted();
            $motion->flushCacheStart(['lines']);
            \Yii::$app->session->setFlash('success', \Yii::t('admin', 'motion_deleted'));
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
                $form = new MotionEditForm($motion->getMyMotionType(), $motion->agendaItem, $motion);
                $form->setAdminMode(true);
                $form->setAttributes([$post, $_FILES]);

                $votingData = $motion->getVotingData();
                $votingData->setFromPostData($post['votes']);
                $motion->setVotingData($votingData);

                $ppChanges = new ProposedProcedureChange(null);
                try {
                    $motion->setProposalVotingPropertiesFromRequest(
                        \Yii::$app->request->post('votingStatus', null),
                        \Yii::$app->request->post('votingBlockId', null),
                        \Yii::$app->request->post('votingItemBlockId', []),
                        \Yii::$app->request->post('votingItemBlockName', ''),
                        \Yii::$app->request->post('newBlockTitle', ''),
                        false,
                        $ppChanges
                    );
                } catch (FormError $e) {
                    \Yii::$app->session->setFlash('error', $e->getMessage());
                }
                if ($ppChanges->hasChanges()) {
                    ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_SET_PROPOSAL, $motion->id, $ppChanges->jsonSerialize());
                }

                $form->saveMotion($motion);
                if (isset($post['sections'])) {
                    $overrides = $post['amendmentOverride'] ?? [];
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

            if (intval($modat['motionType']) !== $motion->motionTypeId) {
                try {
                    /** @var ConsultationMotionType $newType */
                    $newType = ConsultationMotionType::findOne($modat['motionType']);
                    if (!$newType || $newType->consultationId !== $motion->consultationId) {
                        throw new FormError('The new motion type was not found');
                    }
                    $motion->setMotionType($newType);
                } catch (FormError $e) {
                    \Yii::$app->session->setFlash('error', $e->getMessage());
                }
            }

            $motion->title        = $modat['title'];
            $motion->statusString = mb_substr($modat['statusString'], 0, 55);
            $motion->noteInternal = $modat['noteInternal'];
            $motion->status       = intval($modat['status']);
            $motion->agendaItemId = (isset($modat['agendaItemId']) ? intval($modat['agendaItemId']) : null);
            $motion->nonAmendable = (isset($modat['nonAmendable']) ? 1 : 0);


            if (isset($modat['slug']) && preg_match('/^[\w_-]+$/i', $modat['slug'])) {
                $collision = false;
                foreach ($motion->getMyConsultation()->motions as $otherMotion) {
                    if (mb_strtolower($otherMotion->slug ?: '') === mb_strtolower($modat['slug']) && $otherMotion->id !== $motion->id) {
                        $collision = true;
                    }
                }
                if ($collision) {
                    \Yii::$app->session->setFlash('error', \Yii::t('admin', 'motion_url_path_err'));
                } else {
                    $motion->slug = mb_strtolower($modat['slug']);
                }
            }

            $roundedDate = Tools::dateBootstraptime2sql($modat['dateCreation']);
            if (substr($roundedDate, 0, 16) !== substr($motion->dateCreation, 0, 16)) {
                $motion->dateCreation = $roundedDate;
            }

            if ($modat['dateResolution'] !== '') {
                $roundedDate = Tools::dateBootstraptime2sql($modat['dateResolution']);
                if (substr($roundedDate, 0, 16) !== substr($motion->dateResolution ?: '', 0, 16)) {
                    $motion->dateResolution = $roundedDate;
                }
            } else {
                $motion->dateResolution = null;
            }

            if ($modat['datePublication'] !== '') {
                $roundedDate = Tools::dateBootstraptime2sql($modat['datePublication']);
                if (substr($roundedDate, 0, 16) !== substr($motion->datePublication ?: '', 0, 16)) {
                    $motion->datePublication = $roundedDate;
                }
            } else {
                $motion->datePublication = null;
            }

            if ($modat['parentMotionId'] && intval($modat['parentMotionId']) !== $motion->id &&
                $this->consultation->getMotion($modat['parentMotionId'])) {
                $motion->parentMotionId = intval($modat['parentMotionId']);
            } else {
                $motion->parentMotionId = null;
            }

            if ($this->consultation->findMotionWithPrefix($modat['titlePrefix'], $motion)) {
                \Yii::$app->session->setFlash('error', \Yii::t('admin', 'motion_prefix_collision'));
            } else {
                $motion->titlePrefix = $post['motion']['titlePrefix'];
            }

            foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
                $plugin::setMotionExtraSettingsFromForm($motion, $post);
            }

            $motion->save();

            foreach ($this->consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
                if (!$this->isPostSet('tags') || !in_array($tag->id, $post['tags'])) {
                    $motion->unlink('tags', $tag, true);
                } else {
                    try {
                        $motion->link('tags', $tag);
                    } catch (\Exception $e) {
                    }
                }
            }

            $this->saveMotionSupporters($motion);
            $this->saveMotionInitiator($motion);

            $motion->flushCache(true);
            \Yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
        }

        $form = new MotionEditForm($motion->getMyMotionType(), $motion->agendaItem, $motion);
        $form->setAdminMode(true);
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

        $form = new MotionMover($this->consultation, $motion, User::getCurrentUser());

        if ($this->isPostSet('move')) {
            $newMotion = $form->move(\Yii::$app->request->post());
            if ($newMotion) {
                if ($newMotion->consultationId === $this->consultation->id) {
                    return $this->redirect(UrlHelper::createMotionUrl($newMotion));
                } else {
                    Consultation::getCurrent()->flushMotionCache();
                    Consultation::getCurrent()->refresh();

                    return $this->render('moved_other_consultation', ['newMotion' => $newMotion]);
                }
            }
        }

        return $this->render('move', ['form' => $form]);
    }

    public function actionMoveCheck($motionId, $checkType)
    {
        /** @var Motion $motion */
        $motion = $this->consultation->getMotion($motionId);
        if (!$motion) {
            $this->redirect(UrlHelper::createUrl('admin/motion-list/index'));
        }
        $this->checkConsistency($motion);

        $result = null;
        if ($checkType === 'prefix') {
            // Returns true, if the provided motion prefix does not exist in the specified consultation yet
            if (\Yii::$app->request->get('newConsultationId')) {
                $consultationId = intval(\Yii::$app->request->get('newConsultationId'));
            } else {
                $consultationId = $this->consultation->id;
            }

            $newMotionPrefix = \Yii::$app->request->get('newMotionPrefix');
            /** @var Consultation $newConsultation */
            $newConsultation = array_values(array_filter($this->site->consultations, function(Consultation $con) use ($consultationId) {
                return ($con->id === $consultationId);
            }))[0];
            $existingMotion = array_filter($newConsultation->motions, function(Motion $cmpMotion) use ($newMotionPrefix, $motion) {
                return (
                    mb_strtolower($cmpMotion->titlePrefix) === mb_strtolower($newMotionPrefix) &&
                    $cmpMotion->id !== $motion->id
                );
            });
            $result = (count($existingMotion) === 0);
        }

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        return json_encode($result);
    }
}
