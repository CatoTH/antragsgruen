<?php

namespace app\models\forms;

use app\components\HTMLTools;
use app\models\api\imotion\{AmendmentCreateRequest, AmendmentUpdateRequest, AmendmentUpdateSection};
use app\models\db\{Amendment, AmendmentSection, AmendmentSupporter, ConsultationAgendaItem, ConsultationSettingsTag, Motion, User};
use app\models\exceptions\FormError;
use app\models\supportTypes\SupportBase;
use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\models\sectionTypes\{ISectionType, TextSimple};

class AmendmentEditForm
{
    /** @var AmendmentSupporter[] */
    public array $supporters = [];

    /** @var int[] */
    public array $tags = [];

    public array $sections = [];
    public ?int $amendmentId = null;
    public string $reason = '';
    public string $editorial = '';
    public ?int $toAnotherAmendment = null;
    public bool $globalAlternative = false;

    private bool $allowEditingInitiators;
    private bool $allowTextEdit;
    private bool $allowSetTags;
    private bool $adminMode = false;

    private function __construct(
        public Motion $motion,
        public ?ConsultationAgendaItem $agendaItem,
        ?Amendment $amendment,
        public ?int $initSectionId,
        public ?int $initParagraphNo
    )
    {
        /** @var AmendmentSection[] $amendmentSections */
        $amendmentSections = [];
        $motionSections    = [];
        foreach ($motion->getActiveSections() as $section) {
            $motionSections[$section->sectionId] = $section;
        }
        if ($amendment) {
            $this->amendmentId       = $amendment->id;
            $this->supporters        = $amendment->amendmentSupporters;
            $this->reason            = $amendment->changeExplanation;
            $this->editorial         = $amendment->changeEditorial;
            $this->globalAlternative = ($amendment->globalAlternative == 1);
            foreach ($amendment->getActiveSections() as $section) {
                $amendmentSections[$section->sectionId] = $section;
                if ($section->getData() === '' && isset($motionSections[$section->sectionId])) {
                    $data                                            = $motionSections[$section->sectionId]->data;
                    $amendmentSections[$section->sectionId]->data    = $data;
                    $amendmentSections[$section->sectionId]->dataRaw = $data;
                }
            }
            foreach ($amendment->getPublicTopicTags() as $tag) {
                $this->tags[] = $tag->id;
            }
        }
        $this->sections = [];
        foreach ($motion->getMyMotionType()->motionSections as $sectionType) {
            if (!$sectionType->hasAmendments) {
                continue;
            }
            if (isset($amendmentSections[$sectionType->id])) {
                $this->sections[] = $amendmentSections[$sectionType->id];
            } else {
                if (isset($motionSections[$sectionType->id])) {
                    $data        = $motionSections[$sectionType->id]->data;
                    $origSection = $motionSections[$sectionType->id];
                } else {
                    $data        = '';
                    $origSection = null;
                }
                $section = new AmendmentSection();
                $section->sectionId = $sectionType->id;
                $section->data = $data;
                $section->dataRaw = $data;
                $section->public = $sectionType->getSettingsObj()->public;
                $section->cache = '';
                $section->refresh();
                if ($origSection) {
                    $section->setOriginalMotionSection($origSection);
                }
                $this->sections[] = $section;
            }
        }
    }

    public static function createForCreating(Motion $motion, ?ConsultationAgendaItem $agendaItem, ?int $initSectionId, ?int $initParagraphNo): self
    {
        $form = new self($motion, $agendaItem, null, $initSectionId, $initParagraphNo);
        $form->allowEditingInitiators = true;
        $form->allowTextEdit = true;
        $form->allowSetTags = $motion->getMyConsultation()->getSettings()->allowUsersToSetTags;

        return $form;
    }

    public static function createForUserEdit(Amendment $amendment, ?int $initSectionId, ?int $initParagraphNo): self
    {
        $form = new self($amendment->getMyMotion(), $amendment->getMyAgendaItem(), $amendment, $initSectionId, $initParagraphNo);
        $form->allowEditingInitiators = $amendment->canEditInitiators();
        $form->allowTextEdit = $amendment->canEditText();
        $form->allowSetTags = $amendment->getMyConsultation()->getSettings()->allowUsersToSetTags;

        return $form;
    }

    public static function createForAdminEdit(Amendment $amendment, ?int $initSectionId, ?int $initParagraphNo): self
    {
        $con = $amendment->getMyConsultation();
        $form = new self($amendment->getMyMotion(), $amendment->getMyAgendaItem(), $amendment, $initSectionId, $initParagraphNo);
        $form->adminMode = true;
        $form->allowEditingInitiators = User::havePrivilege($con, Privileges::PRIVILEGE_MOTION_INITIATORS, PrivilegeQueryContext::amendment($amendment));
        $form->allowTextEdit = User::havePrivilege($con, Privileges::PRIVILEGE_MOTION_TEXT_EDIT, PrivilegeQueryContext::amendment($amendment));
        $form->allowSetTags = true;

        return $form;
    }

    public function getAllowEditinginitiators(): bool
    {
        return $this->allowEditingInitiators;
    }

    public function cloneSupporters(Amendment $amendment): void
    {
        foreach ($amendment->amendmentSupporters as $supp) {
            $suppNew = new AmendmentSupporter();
            $suppNew->setAttributes($supp->getAttributes());
            $suppNew->dateCreation = date('Y-m-d H:i:s');
            $suppNew->extraData    = $supp->extraData;
            $this->supporters[]    = $suppNew;
        }
    }

    public function cloneAmendmentText(Amendment $amendment, bool $includeReason): void
    {
        if ($includeReason) {
            $this->reason    = $amendment->changeExplanation;
        }
        $this->editorial = $amendment->changeEditorial;
        /** @var AmendmentSection[] $byId */
        $byId = [];
        foreach ($amendment->getActiveSections() as $section) {
            $byId[$section->sectionId] = $section;
        }
        foreach ($this->sections as $section) {
            if (isset($byId[$section->sectionId])) {
                $section->data    = $byId[$section->sectionId]->data;
                $section->dataRaw = $byId[$section->sectionId]->dataRaw;
            }
        }
    }

    /**
     * @param AmendmentUpdateSection[] $requestSections
     * @throws FormError
     */
    private function setAndVerifySectionContent(array $requestSections): void
    {
        $requestSectionsById = [];
        foreach ($requestSections as $requestSection) {
            $requestSectionsById[$requestSection->sectionId] = $requestSection;
        }

        $errors = [];
        foreach ($this->sections as $section) {
            $sectionSettings = $section->getSettings();
            $requestSection = $requestSectionsById[$section->sectionId] ?? null;
            if ($requestSection === null) {
                continue;
            }

            $sectionType = $section->getSectionType();
            if ($this->adminMode && $sectionSettings->type === ISectionType::TYPE_TEXT_SIMPLE) {
                /** @var TextSimple $sectionType */
                $sectionType->forceMultipleParagraphMode(true);
            }
            /** @var ISectionType $sectionType */

            if ($requestSection->getRawData() !== null) {
                $sectionType->setAmendmentData($requestSection->getRawData());
            } elseif ($requestSection->data !== null) {
                if ($sectionSettings->type === ISectionType::TYPE_TEXT_SIMPLE) {
                    $sectionType->setAmendmentData(['consolidated' => $requestSection->data, 'raw' => '']);
                } else {
                    $sectionType->setAmendmentData($requestSection->data);
                }
            }

            if ($section->data == '' && $sectionSettings->required) {
                $errors[] = str_replace('%FIELD%', $sectionSettings->title, \Yii::t('base', 'err_no_data_given'));
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%MAX%', (string)$sectionSettings->maxLen, \Yii::t('base', 'err_max_len_exceed'));
            }
        }

        if (count($errors) > 0) {
            throw new FormError($errors);
        }
    }

    /**
     * @param AmendmentSupporter[] $initiators
     * @param AmendmentSupporter[] $supporters
     * @throws FormError
     */
    private function validateInitiators(SupportBase $supportForm, array $initiators, array $supporters): void
    {
        if ($supportForm->requiresInitiator() && count($initiators) === 0) {
            throw new FormError(\Yii::t('motion', 'err_no_initiator'));
        }
        foreach ($initiators as $initiator) {
            // Is only one at the moment
            $supportForm->validateMotion($initiator, $supporters);
        }
    }

    /**
     * @throws FormError
     * @throws \Throwable
     * @throws \app\models\exceptions\NotAmendable
     */
    public function createAmendment(AmendmentCreateRequest $dto): Amendment
    {
        $consultation = $this->motion->getMyConsultation();

        if (!$this->motion->isCurrentlyAmendable()) {
            throw new FormError(\Yii::t('amend', 'err_create_permission'));
        }

        $amendment = new Amendment();
        $supportForm = $this->motion->motionType->getAmendmentSupportTypeClass();

        // 1. Set data from DTO (retained on $this for re-render if validation fails)
        $this->reason = $dto->reason !== null ? HTMLTools::cleanSimpleHtml($dto->reason) : '';
        $this->editorial = $dto->editorial !== null ? HTMLTools::cleanSimpleHtml($dto->editorial) : '';
        $this->globalAlternative = ($dto->globalAlternative ?? false) && $consultation->getSettings()->globalAlternatives;
        $this->toAnotherAmendment = $dto->amendingAmendmentId;

        if ($this->allowSetTags) {
            $this->tags = $dto->tags ?? [];
        }

        $initiators = $supportForm->getAmendmentInitiatorsFromDto($amendment, $dto->initiators);
        $supporters = $supportForm->getAmendmentSupportersFromDto($amendment, $dto->supporters ?? []);
        $this->supporters = array_merge($initiators, $supporters);

        // 2. Validate
        $this->setAndVerifySectionContent($dto->sections);
        $this->validateInitiators($supportForm, $initiators, $supporters);

        // 3. Save
        $amendment->status = Motion::STATUS_DRAFT;
        $amendment->statusString = '';
        $amendment->motionId = $this->motion->id;
        $amendment->textFixed = ($consultation->getSettings()->adminsMayEdit ? 0 : 1);
        $amendment->titlePrefix = '';
        $amendment->dateCreation = date('Y-m-d H:i:s');
        $amendment->dateContentModification = date('Y-m-d H:i:s');
        if ($this->allowTextEdit) {
            $amendment->changeEditorial = $this->editorial;
            $amendment->changeExplanation = $this->reason;
            $amendment->globalAlternative = ($this->globalAlternative ? 1 : 0);
        }
        $amendment->agendaItemId = $this->agendaItem?->id;
        $amendment->changeText = '';
        $amendment->cache = '';

        if ($this->toAnotherAmendment) {
            $amendment->amendingAmendmentId = $this->toAnotherAmendment;
        }

        if (!$amendment->save()) {
            throw new FormError(\Yii::t('amend', 'err_create'));
        }

        if ($this->allowEditingInitiators) {
            $supportForm->submitAmendment($amendment, $this->supporters);
        }

        if ($this->allowSetTags) {
            $amendment->setTags(ConsultationSettingsTag::TYPE_PUBLIC_AMENDMENT, $this->tags);
        }

        if ($this->allowTextEdit) {
            foreach ($this->sections as $section) {
                $section->amendmentId = $amendment->id;
                $section->save();
            }
        }

        $amendment->save();

        return $amendment;
    }

    /**
     * @throws FormError
     * @throws \Throwable
     * @throws \app\models\exceptions\NotAmendable
     */
    public function saveAmendment(Amendment $amendment, AmendmentUpdateRequest $dto): void
    {
        if (!$this->adminMode && !$this->allowTextEdit) {
            throw new FormError(\Yii::t('motion', 'err_create_permission'));
        }

        $consultation = $this->motion->getMyConsultation();
        $ctx = PrivilegeQueryContext::amendment($amendment);
        $supportForm = $this->motion->getMyMotionType()->getAmendmentSupportTypeClass();
        $supportForm->setAdminMode($this->adminMode);

        // 1. Set data from DTO (retained on $this for re-render if validation fails)
        if ($this->allowTextEdit) {
            $this->reason = $dto->reason !== null ? HTMLTools::cleanSimpleHtml($dto->reason) : '';
            $this->editorial = $dto->editorial !== null ? HTMLTools::cleanSimpleHtml($dto->editorial) : '';
            $this->globalAlternative = ($dto->globalAlternative ?? false) && $consultation->getSettings()->globalAlternatives;
        }

        $initiators = $supportForm->getAmendmentInitiatorsFromDto($amendment, $dto->initiators);
        $supporters = $supportForm->getAmendmentSupportersFromDto($amendment, $dto->supporters ?? []);
        $this->supporters = array_merge($initiators, $supporters);

        if ($this->allowSetTags) {
            $this->tags = $dto->tags ?? [];
        } else {
            $this->tags = [];
            foreach ($amendment->getPublicTopicTags() as $tag) {
                $this->tags[] = $tag->id;
            }
        }

        // 2. Validate
        if ($this->allowTextEdit) {
            $this->setAndVerifySectionContent($dto->sections);
        }
        if ($this->allowEditingInitiators) {
            $this->validateInitiators($supportForm, $initiators, $supporters);
        }

        // 3. Save
        if (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_TEXT_EDIT, $ctx)) {
            $amendment->changeExplanation = $this->reason;
            $amendment->changeEditorial = $this->editorial;
            $amendment->globalAlternative = ($this->globalAlternative ? 1 : 0);
        }

        if ($amendment->save()) {
            if ($this->allowEditingInitiators && (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_INITIATORS, $ctx))) {
                $supportForm->submitAmendment($amendment, $this->supporters);
            }

            if ($consultation->getSettings()->allowUsersToSetTags || $this->adminMode) {
                $amendment->setTags(ConsultationSettingsTag::TYPE_PUBLIC_AMENDMENT, $this->tags);
            }

            if (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_TEXT_EDIT, $ctx)) {
                foreach ($amendment->getActiveSections() as $section) {
                    $section->delete();
                }
                foreach ($this->sections as $section) {
                    // Just saving the old section might fail as it might have been deleted by a different object instance above.
                    $clonedSection = new AmendmentSection();
                    $clonedSection->setAttributes($section->getAttributes(), false);
                    $clonedSection->amendmentId = $amendment->id;
                    $clonedSection->save();
                }
            }

            $amendment->dateContentModification = date('Y-m-d H:i:s');
            $amendment->save();
        } else {
            throw new FormError(\Yii::t('base', 'err_unknown'));
        }
    }
}
