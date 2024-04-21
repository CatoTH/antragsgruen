<?php

namespace app\controllers\admin;

use app\views\pdfLayouts\IPDFLayout;
use app\components\{DateTools, UrlHelper};
use app\models\db\{ConsultationMotionType, ConsultationSettingsMotionSection, ConsultationUserGroup, TexTemplate, User};
use app\models\exceptions\{ExceptionBase, FormError};
use app\models\forms\DeadlineForm;
use app\models\http\{HtmlErrorResponse, HtmlResponse, RedirectResponse, ResponseInterface};
use app\models\motionTypeTemplates\Application as ApplicationTemplate;
use app\models\motionTypeTemplates\Motion as MotionTemplate;
use app\models\motionTypeTemplates\PDFApplication as PDFApplicationTemplate;
use app\models\motionTypeTemplates\Statutes as StatutesTemplate;
use app\models\motionTypeTemplates\ProgressReport as ProgressReportTemplate;
use app\models\policies\{All, IPolicy, Nobody, UserGroups};
use app\models\settings\{InitiatorForm, MotionSection, MotionType, Privileges};
use app\models\supportTypes\SupportBase;

class MotionTypeController extends AdminBase
{
    public const REQUIRED_PRIVILEGES = [
        Privileges::PRIVILEGE_CONSULTATION_SETTINGS,
    ];

    /**
     * @throws FormError
     */
    private function sectionsSave(ConsultationMotionType $motionType): void
    {
        $position = 0;
        if (!$this->getHttpRequest()->post('sections')) {
            return;
        }
        foreach ($this->getHttpRequest()->post('sections') as $sectionId => $data) {
            if (preg_match('/^new[0-9]+$/', $sectionId)) {
                $section               = new ConsultationSettingsMotionSection();
                $section->motionTypeId = $motionType->id;
                $section->type         = intval($data['type']);
                $section->status       = ConsultationSettingsMotionSection::STATUS_VISIBLE;

                $settings = $section->getSettingsObj();
                $settings->public = (isset($data['nonPublic']) ? MotionSection::PUBLIC_NO : MotionSection::PUBLIC_YES);
                $section->setSettingsObj($settings);
            } else {
                /** @var ConsultationSettingsMotionSection|null $section */
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
        foreach ($this->getHttpRequest()->post('sectionsTodelete') as $sectionId) {
            if ($sectionId > 0) {
                $sectionId = intval($sectionId);
                /** @var ConsultationSettingsMotionSection|null $section */
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
            $groups = ConsultationUserGroup::loadGroupsByIdForConsultation($motionType->getConsultation(), $data['groups'] ?? []);
            $policy->setAllowedUserGroups($groups);
        }
        return $policy;
    }

    public function actionType(string $motionTypeId): ResponseInterface
    {
        $motionTypeId = intval($motionTypeId);

        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            return new HtmlErrorResponse(404, $e->getMessage());
        }
        if ($this->isPostSet('delete')) {
            if ($motionType->isDeletable()) {
                $motionType->status = ConsultationMotionType::STATUS_DELETED;
                $motionType->save();

                return new HtmlResponse($this->render('deleted'));
            } else {
                $this->getHttpSession()->setFlash('error', \Yii::t('admin', 'motion_type_not_deletable'));
            }
        }
        if ($this->isPostSet('save')) {
            $input = $this->getHttpRequest()->post('type');
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

            $deadlineForm = DeadlineForm::createFromInput($this->getHttpRequest()->post('deadlines'));
            $motionType->setAllDeadlines($deadlineForm->generateDeadlineArray());

            $pdfTemplate = $this->getHttpRequest()->post('pdfTemplate', '');
            foreach (IPDFLayout::getSelectablePdfLayouts() as $layout) {
                if ($layout->getHtmlId() === $pdfTemplate) {
                    $motionType->pdfLayout = $layout->id ?? 0;
                    $motionType->texTemplateId = $layout->latexId;
                }
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
            $settings->commentsRestrictViewToWritables = isset($input['commentsRestrictViewToWritables']);
            $settings->allowAmendmentsToAmendments = isset($input['allowAmendmentsToAmendments']);
            $settings->showProposalsInExports = isset($input['showProposalsInExports']);
            $motionType->setSettingsObj($settings);

            // Motion Initiators / Supporters
            $settings = $motionType->getMotionSupportTypeClass()->getSettingsObj();
            $settings->saveFormTyped(
                $this->getHttpRequest()->post('motionInitiatorSettings', []),
                $this->getHttpRequest()->post('motionInitiatorSettingFields', [])
            );
            $settings->initiatorCanBeOrganization = $this->isPostSet('initiatorCanBeOrganization');
            $settings->initiatorCanBePerson       = $this->isPostSet('initiatorCanBePerson');
            if (!$settings->initiatorCanBePerson && !$settings->initiatorCanBeOrganization) {
                // Probably a mistake
                $settings->initiatorCanBeOrganization = true;
                $settings->initiatorCanBePerson       = true;
            }
            if (isset($input['initiatorSetPermissions'])) {
                $settings->setInitiatorPersonPolicyObject($this->getPolicyFromUpdateData($motionType, $input['initiatorPersonPolicy']));
                $settings->setInitiatorOrganizationPolicyObject($this->getPolicyFromUpdateData($motionType, $input['initiatorOrgaPolicy']));
            } else {
                $settings->setInitiatorPersonPolicyObject(new All($this->consultation, $settings, null));
                $settings->setInitiatorOrganizationPolicyObject(new All($this->consultation, $settings, null));
            }
            $motionType->supportTypeMotions = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

            if ($this->isPostSet('sameInitiatorSettingsForAmendments')) {
                $motionType->supportTypeAmendments = null;
            } else {
                // Amendment Initiators / Supporters
                $settings = $motionType->getAmendmentSupportTypeClass()->getSettingsObj();
                $settings->saveFormTyped(
                    $this->getHttpRequest()->post('amendmentInitiatorSettings', []),
                    $this->getHttpRequest()->post('amendmentInitiatorSettingFields', [])
                );
                $settings->initiatorCanBeOrganization = $this->isPostSet('amendmentInitiatorCanBeOrganization');
                $settings->initiatorCanBePerson       = $this->isPostSet('amendmentInitiatorCanBePerson');
                if (!$settings->initiatorCanBePerson && !$settings->initiatorCanBeOrganization) {
                    // Probably a mistake
                    $settings->initiatorCanBeOrganization = true;
                    $settings->initiatorCanBePerson       = true;
                }
                if (isset($input['amendmentInitiatorSetPermissions'])) {
                    $settings->setInitiatorPersonPolicyObject($this->getPolicyFromUpdateData($motionType, $input['amendmentInitiatorPersonPolicy']));
                    $settings->setInitiatorOrganizationPolicyObject($this->getPolicyFromUpdateData($motionType, $input['amendmentInitiatorOrgaPolicy']));
                } else {
                    $settings->setInitiatorPersonPolicyObject(new All($this->consultation, $settings, null));
                    $settings->setInitiatorOrganizationPolicyObject(new All($this->consultation, $settings, null));
                }
                if (is_numeric($this->getHttpRequest()->post('maxPdfSupporters'))) {
                    $settings->maxPdfSupporters = intval($this->getPostValue('maxPdfSupporters'));
                } else {
                    $settings->maxPdfSupporters = null;
                }
                $motionType->supportTypeAmendments = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            }

            $motionType->save();

            $this->sectionsSave($motionType);
            $this->sectionsDelete($motionType);

            DateTools::setDeadlineDebugMode($this->consultation, $this->isPostSet('activateDeadlineDebugMode'));

            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'saved'));
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
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'motion_type_created_msg'));
        }

        return new HtmlResponse($this->render('type', [
            'motionType'               => $motionType,
            'supportCollPolicyWarning' => $supportCollPolicyWarning
        ]));
    }

    public function actionTypecreate(): ResponseInterface
    {
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null)) {
            return new HtmlErrorResponse(403, \Yii::t('admin', 'no_access'));
        }

        if ($this->isPostSet('create')) {
            $type         = $this->getHttpRequest()->post('type');
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
            } elseif (isset($type['preset']) && $type['preset'] === 'progress') {
                $motionType = ProgressReportTemplate::doCreateProgressType($this->consultation);
                ProgressReportTemplate::doCreateProgressSections($motionType);
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
                    $motionType->supportTypeMotions    = json_encode($initiatorSettings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                    $motionType->supportTypeAmendments = null;

                    $motionType->setSettingsObj(new MotionType(null));

                    /** @var TexTemplate[] $texTemplates */
                    $texTemplates              = TexTemplate::find()->all();
                    $motionType->texTemplateId = (count($texTemplates) > 0 ? $texTemplates[0]->id : null);
                }
            }
            $motionType->titleSingular = $type['titleSingular'];
            $motionType->titlePlural   = $type['titlePlural'];
            $motionType->createTitle   = $type['createTitle'];
            $motionType->motionPrefix  = substr($type['motionPrefix'], 0, 10);

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

            $url = UrlHelper::createUrl(['/admin/motion-type/type', 'motionTypeId' => $motionType->id, 'msg' => 'created']);
            return new RedirectResponse($url);
        }

        return new HtmlResponse($this->render('create'));
    }
}
