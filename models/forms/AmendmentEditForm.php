<?php

namespace app\models\forms;

use app\components\HTMLTools;
use app\models\api\imotion\{AmendmentCreateRequest, AmendmentUpdateRequest, AmendmentUpdateSection};
use app\models\db\{Amendment,
    AmendmentSection,
    AmendmentSupporter,
    ConsultationAgendaItem,
    ConsultationSettingsTag,
    Motion,
    User};
use app\models\exceptions\FormError;
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
    private bool $adminMode = false;
    private bool $allowEditingInitiators = true; // Only affects updating

    public function __construct(
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

    public function setAdminMode(bool $set): void
    {
        $this->adminMode = $set;
    }

    public function setAllowEditingInitiators(bool $set): void
    {
        $this->allowEditingInitiators = $set;
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

    private function getInitiatorSupporter(): ?AmendmentSupporter
    {
        foreach ($this->supporters as $supporter) {
            if ($supporter->role === AmendmentSupporter::ROLE_INITIATOR) {
                return $supporter;
            }
        }
        return null;
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
     * @throws FormError
     */
    private function createAmendmentVerify(): void
    {
        $errors = [];
        try {
            $initiator = $this->getInitiatorSupporter();
            if ($initiator) {
                $this->motion->getMyMotionType()->getAmendmentSupportTypeClass()->validateAmendment($initiator, $this->supporters);
            }
        } catch (FormError $e) {
            $errors = array_merge($errors, $e->getMessages());
        }
        if (count($errors) > 0) {
            throw new FormError($errors);
        }
    }

    /**
     * @throws FormError
     */
    private function saveAmendmentVerify(): void
    {
        if ($this->allowEditingInitiators) {
            $initiator = $this->getInitiatorSupporter();
            if ($initiator) {
                $this->motion->getMyMotionType()->getAmendmentSupportTypeClass()->validateAmendment($initiator, $this->supporters);
            }
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
        $supportType = $this->motion->motionType->getAmendmentSupportTypeClass();

        // 1. Set data from DTO (retained on $this for re-render if validation fails)
        $this->reason = $dto->reason !== null ? HTMLTools::cleanSimpleHtml($dto->reason) : '';
        $this->editorial = $dto->editorial !== null ? HTMLTools::cleanSimpleHtml($dto->editorial) : '';
        $this->globalAlternative = ($dto->globalAlternative ?? false) && $consultation->getSettings()->globalAlternatives;
        $this->toAnotherAmendment = $dto->amendingAmendmentId;

        if ($consultation->getSettings()->allowUsersToSetTags || $this->adminMode) {
            $this->tags = $dto->tags ?? [];
        }

        $initiators = $supportType->getAmendmentInitiatorsFromDto($amendment, $dto->initiators);
        $supporters = $supportType->getAmendmentSupportersFromDto($amendment, $dto->supporters ?? []);
        $this->supporters = array_merge($initiators, $supporters);

        // 2. Validate
        $this->setAndVerifySectionContent($dto->sections);
        $this->createAmendmentVerify();

        // 3. Save
        $amendment->status = Motion::STATUS_DRAFT;
        $amendment->statusString = '';
        $amendment->motionId = $this->motion->id;
        $amendment->textFixed = ($consultation->getSettings()->adminsMayEdit ? 0 : 1);
        $amendment->titlePrefix = '';
        $amendment->dateCreation = date('Y-m-d H:i:s');
        $amendment->dateContentModification = date('Y-m-d H:i:s');
        $amendment->changeEditorial = $this->editorial;
        $amendment->changeExplanation = $this->reason;
        $amendment->globalAlternative = ($this->globalAlternative ? 1 : 0);
        $amendment->agendaItemId = $this->agendaItem?->id;
        $amendment->changeText = '';
        $amendment->cache = '';

        if ($this->toAnotherAmendment) {
            $amendment->amendingAmendmentId = $this->toAnotherAmendment;
        }

        if ($amendment->save()) {
            $supportType->submitAmendment($amendment, $this->supporters);

            if ($consultation->getSettings()->allowUsersToSetTags || $this->adminMode) {
                $amendment->setTags(ConsultationSettingsTag::TYPE_PUBLIC_AMENDMENT, $this->tags);
            }

            foreach ($this->sections as $section) {
                $section->amendmentId = $amendment->id;
                $section->save();
            }

            $amendment->save();

            return $amendment;
        } else {
            throw new FormError(\Yii::t('amend', 'err_create'));
        }
    }

    /**
     * @throws FormError
     * @throws \Throwable
     * @throws \app\models\exceptions\NotAmendable
     */
    public function saveAmendment(Amendment $amendment, AmendmentUpdateRequest $dto): void
    {
        $consultation = $this->motion->getMyConsultation();
        $ctx = PrivilegeQueryContext::amendment($amendment);
        $supportType = $this->motion->getMyMotionType()->getAmendmentSupportTypeClass();

        // 1. Set data from DTO (retained on $this for re-render if validation fails)
        if (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_TEXT_EDIT, $ctx)) {
            $this->reason = $dto->reason !== null ? HTMLTools::cleanSimpleHtml($dto->reason) : '';
            $this->editorial = $dto->editorial !== null ? HTMLTools::cleanSimpleHtml($dto->editorial) : '';
            $this->globalAlternative = ($dto->globalAlternative ?? false) && $consultation->getSettings()->globalAlternatives;
        }

        if ($this->allowEditingInitiators && (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_INITIATORS, $ctx))) {
            $initiators = $supportType->getAmendmentInitiatorsFromDto($amendment, $dto->initiators);
            $supporters = $supportType->getAmendmentSupportersFromDto($amendment, $dto->supporters ?? []);
            $this->supporters = array_merge($initiators, $supporters);
        }

        if ($consultation->getSettings()->allowUsersToSetTags || $this->adminMode) {
            $this->tags = $dto->tags ?? [];
        } else {
            $this->tags = [];
            foreach ($amendment->getPublicTopicTags() as $tag) {
                $this->tags[] = $tag->id;
            }
        }

        // 2. Validate
        if (!$this->adminMode) {
            if (!$amendment->canEditText()) {
                throw new FormError(\Yii::t('amend', 'err_create_permission'));
            }
            $this->setAndVerifySectionContent($dto->sections);
            $this->saveAmendmentVerify();
        } elseif (User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_TEXT_EDIT, $ctx)) {
            $this->setAndVerifySectionContent($dto->sections);
        }

        // 3. Save
        if (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_TEXT_EDIT, $ctx)) {
            $amendment->changeExplanation = $this->reason;
            $amendment->changeEditorial = $this->editorial;
            $amendment->globalAlternative = ($this->globalAlternative ? 1 : 0);
        }

        if ($amendment->save()) {
            if ($this->allowEditingInitiators && (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_INITIATORS, $ctx))) {
                $supportType->submitAmendment($amendment, $this->supporters);
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
