<?php

namespace app\models\forms;

use app\components\HTMLTools;
use app\models\settings\{AntragsgruenApp, PrivilegeQueryContext, Privileges};
use app\models\events\MotionEvent;
use app\models\exceptions\Internal;
use app\models\db\{Consultation,
    ConsultationAgendaItem,
    ConsultationMotionType,
    ConsultationSettingsMotionSection,
    ConsultationSettingsTag,
    Motion,
    MotionSection,
    MotionSupporter,
    User};
use app\models\api\imotion\{MotionCreateRequest, MotionUpdateRequest, MotionUpdateSection};
use app\models\exceptions\FormError;
use app\models\sectionTypes\ISectionType;
use app\models\supportTypes\SupportBase;

class MotionEditForm
{
    /**
     * @var MotionSupporter[]
     * Are initialized exclusively from the request.
     * Only after validation it's merged into potentially existing previous supporters.
     */
    public array $supporters = [];

    /**
     * @var MotionSection[]
     * Are first initialized using empty sections, then (if applicable) from the existing motion. Finally, content is set from request.
     * After validation, it's simply safed (not merged)
     */
    public array $sections = [];

    /** @var int[] */
    public array $tags = [];

    public ?int $motionId = null;

    private bool $allowEditingInitiators;
    private bool $allowTextEdit;
    private bool $allowSetTags;
    private bool $adminMode = false;

    private function __construct(
        public readonly ConsultationMotionType $motionType,
        public ?ConsultationAgendaItem $agendaItem
    ) {
    }

    public static function createForCreating(Consultation $consultation, ConsultationMotionType $motionType, ?ConsultationAgendaItem $agendaItem): self
    {
        $form = new self($motionType, $agendaItem);
        $form->initializeSectionsAndTags(null);
        $form->allowEditingInitiators = true;
        $form->allowTextEdit = true;
        $form->allowSetTags = $consultation->getSettings()->allowUsersToSetTags;

        return $form;
    }

    public static function createForUserEdit(Motion $motion): self
    {
        $form = new self($motion->getMyMotionType(), $motion->agendaItem);
        $form->initializeSectionsAndTags($motion);
        $form->allowEditingInitiators = $motion->canEditInitiators();
        $form->allowTextEdit = $motion->canEditText();
        $form->allowSetTags = $motion->getMyConsultation()->getSettings()->allowUsersToSetTags;

        return $form;
    }

    public static function createForAdminEdit(Motion $motion): self
    {
        $con = $motion->getMyConsultation();
        $form = new self($motion->getMyMotionType(), $motion->agendaItem);
        $form->initializeSectionsAndTags($motion);
        $form->adminMode = true;
        $form->allowEditingInitiators = User::havePrivilege($con, Privileges::PRIVILEGE_MOTION_INITIATORS, PrivilegeQueryContext::motion($motion));
        $form->allowTextEdit = User::havePrivilege($con, Privileges::PRIVILEGE_MOTION_TEXT_EDIT, PrivilegeQueryContext::motion($motion));
        $form->allowSetTags = true;

        return $form;
    }

    /**
     * @throws Internal
     * @throws \app\models\exceptions\NotFound
     * @return array{ConsultationMotionType, ConsultationAgendaItem|null}
     */
    public static function getMotionTypeForCreate(Consultation $consultation, ?int $motionTypeId, ?int $agendaItemId, ?int $cloneFrom): array
    {
        if ($agendaItemId > 0) {
            $agendaItem = $consultation->getAgendaItem($agendaItemId);
            if (!$agendaItem) {
                throw new Internal('Could not find agenda item');
            }
            if (!$agendaItem->getMyMotionType()) {
                throw new Internal('Agenda item does not have motions');
            }
            $motionType = $agendaItem->getMyMotionType();
        } elseif ($motionTypeId > 0) {
            $motionType = $consultation->getMotionType($motionTypeId);
            $agendaItem = null;
        } elseif ($cloneFrom > 0) {
            $motion = $consultation->getMotion($cloneFrom);
            if (!$motion) {
                throw new Internal('Could not find referenced motion');
            }
            $motionType = $motion->getMyMotionType();
            $agendaItem = $motion->agendaItem;
        } else {
            throw new Internal('Could not resolve motion type');
        }

        return [$motionType, $agendaItem];
    }

    public function initializeSectionsAndTags(?Motion $motion): void
    {
        $motionSections = [];
        if ($motion) {
            $this->motionId   = $motion->id;
            $this->supporters = $motion->motionSupporters;
            foreach ($motion->getActiveSections(null, true) as $section) {
                $motionSections[$section->sectionId] = $section;
            }
        }
        $this->sections = [];
        foreach ($this->motionType->motionSections as $sectionType) {
            if (isset($motionSections[$sectionType->id])) {
                $this->sections[] = $motionSections[$sectionType->id];
            } else {
                $this->sections[] = MotionSection::createEmpty($sectionType->id, $sectionType->getSettingsObj()->public);
            }
        }

        $this->tags = [];
        if ($motion) {
            foreach ($motion->getPublicTopicTags() as $tag) {
                $this->tags[] = $tag->id;
            }
        }
    }

    public function getAllowEditinginitiators(): bool
    {
        return $this->allowEditingInitiators;
    }

    public function cloneSupporters(Motion $motion): void
    {
        foreach ($motion->motionSupporters as $supp) {
            $suppNew = new MotionSupporter();
            $suppNew->setAttributes($supp->getAttributes());
            $suppNew->dateCreation = date('Y-m-d H:i:s');
            $suppNew->extraData    = $supp->extraData;
            $this->supporters[]    = $suppNew;
        }
    }

    public function cloneMotionText(Motion $motion): void
    {
        /** @var MotionSection[] $byId */
        $byId = [];
        foreach ($motion->getActiveSections() as $section) {
            $byId[$section->sectionId] = $section;
        }
        foreach ($this->sections as $section) {
            if (isset($byId[$section->sectionId])) {
                $section->setData($byId[$section->sectionId]->getData());
                $section->dataRaw = $byId[$section->sectionId]->dataRaw;
            }
        }
    }

    /**
     * @param MotionUpdateSection[] $requestSections
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
            if ($requestSection->data !== null) {
                $section->getSectionType()->setMotionData($requestSection->data);
            } else {
                $section->getSectionType()->deleteMotionData();
            }

            if ($section->getData() === '' && $sectionSettings->required === ConsultationSettingsMotionSection::REQUIRED_YES) {
                $errors[] = str_replace('%FIELD%', $sectionSettings->title, \Yii::t('base', 'err_no_data_given'));
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%MAX%', (string) $sectionSettings->maxLen, \Yii::t('base', 'err_max_len_exceed'));
            }
        }

        if (count($errors) > 0) {
            throw new FormError($errors);
        }
    }

    /**
     * @param MotionSupporter[] $initiators
     * @param MotionSupporter[] $supporters
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
     * Returns true, if the rewriting was successful.
     *
     * @param MotionUpdateSection[] $newSections
     * @param array<int, array<int, string[]>> $overrides
     */
    private function updateTextRewritingAmendments(Motion $motion, array $newSections, array $overrides): bool
    {
        $unsanitizedHtml = [];
        foreach ($newSections as $requestSection) {
            $unsanitizedHtml[$requestSection->sectionId] = $requestSection->data;
        }

        /** @var string[] $newHtmls */
        $newHtmls = [];
        foreach ($motion->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
            if (!isset($unsanitizedHtml[$section->sectionId])) {
                throw new FormError('No content found for section ' . $section->sectionId);
            }
            $forbiddenFormattings = $section->getSettings()->getForbiddenMotionFormattings();
            $newHtmls[$section->sectionId] = HTMLTools::cleanSimpleHtml($unsanitizedHtml[$section->sectionId], $forbiddenFormattings);
        }

        foreach ($motion->getAmendmentsRelevantForCollisionDetection() as $amendment) {
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                if (isset($overrides[$amendment->id]) && isset($overrides[$amendment->id][$section->sectionId])) {
                    $sectionOverrides = $overrides[$amendment->id][$section->sectionId];
                } else {
                    $sectionOverrides = [];
                }
                if (!$section->canRewrite($newHtmls[$section->sectionId], $sectionOverrides)) {
                    return false;
                }
            }
        }

        foreach ($motion->getAmendmentsRelevantForCollisionDetection() as $amendment) {
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                if (isset($overrides[$amendment->id]) && isset($overrides[$amendment->id][$section->sectionId])) {
                    $sectionOverrides = $overrides[$amendment->id][$section->sectionId];
                } else {
                    $sectionOverrides = [];
                }
                $section->performRewrite($newHtmls[$section->sectionId], $sectionOverrides);
                $section->save();
            }
        }

        foreach ($motion->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
            $section->setData($newHtmls[$section->sectionId]);
            $section->save();
        }

        $this->initializeSectionsAndTags($motion);

        return true;
    }

    private function getInitialVersion(Consultation $consultation, Motion $motion): string
    {
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $version = $plugin::getInitialMotionVersion($consultation, $motion);
            if ($version !== null) {
                return $version;
            }
        }

        return Motion::VERSION_DEFAULT;
    }

    private function overwriteSections(Motion $motion): void
    {
        /** @var MotionSection[] $sectionsById */
        $sectionsById = [];
        foreach ($motion->getActiveSections() as $section) {
            $sectionsById[$section->sectionId] = $section;
        }
        foreach ($this->sections as $section) {
            if ($this->motionId && $section->getSettings()->type === ISectionType::TYPE_TEXT_SIMPLE) {
                // Updating the text is done separately, including amendment rewriting
                continue;
            }
            if (isset($sectionsById[$section->sectionId])) {
                $section = $sectionsById[$section->sectionId];
            }
            $section->motionId = $motion->id;
            $section->save();
        }
    }

    /**
     * @throws FormError
     * @throws \Exception
     */
    public function createMotion(MotionCreateRequest $dto, bool $asDraft): Motion
    {
        if (!$this->motionType->getMotionPolicy()->checkCurrUserMotion()) {
            throw new FormError(\Yii::t('motion', 'err_create_permission'));
        }

        $consultation = $this->motionType->getConsultation();
        $supportForm = $this->motionType->getMotionSupportTypeClass();

        // 1. Set data, but don't validate yet
        $this->initializeSectionsAndTags(null);
        $initiators = $supportForm->getMotionInitiatorsFromDto($this->motionType, $dto->initiators);
        $supporters = $supportForm->getMotionSupportersFromDto($this->motionType, $dto->supporters ?? []);
        $this->supporters = array_merge($initiators, $supporters); // Used by edit form in case validation fails

        if ($dto->agendaItemId !== null) {
            foreach ($this->motionType->agendaItems as $agendaItem) {
                if ($agendaItem->id === $dto->agendaItemId) {
                    $this->agendaItem = $agendaItem;
                }
            }
        }
        if ($this->allowSetTags) {
            $this->tags = $dto->tags ?? [];
        }

        // 2. Validate data
        $this->setAndVerifySectionContent($dto->sections);
        $this->validateInitiators($supportForm, $initiators, $supporters);

        // 3. Save

        $motion = new Motion();
        $motion->status = Motion::STATUS_DRAFT;
        $motion->consultationId = $this->motionType->consultationId;
        $motion->textFixed = ($consultation->getSettings()->adminsMayEdit ? 0 : 1);
        $motion->title = '';
        $motion->titlePrefix = '';
        $motion->version = $this->getInitialVersion($consultation, $motion);
        $motion->dateCreation = date('Y-m-d H:i:s');
        $motion->dateContentModification = date('Y-m-d H:i:s');
        $motion->motionTypeId = $this->motionType->id;
        $motion->cache = '';
        $motion->agendaItemId = $this->agendaItem?->id;

        if (!$motion->save()) {
            throw new FormError(\Yii::t('motion', 'err_create'));
        }

        $supportForm->submitMotion($motion, $this->supporters);

        if ($this->allowSetTags) {
            $motion->setTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, $dto->tags ?? []);
        }

        foreach ($this->sections as $section) {
            $section->motionId = $motion->id;
            $section->save();
        }

        $motion->refreshTitle();
        $motion->slug = $motion->createSlug();
        $motion->save();

        if (!$asDraft) {
            $motion->trigger(Motion::EVENT_CREATED, new MotionEvent($motion));

            if ($motion->status === Motion::STATUS_SUBMITTED_SCREENED) {
                $motion->trigger(Motion::EVENT_PUBLISHED, new MotionEvent($motion));
            }
        }

        return $motion;
    }

    /**
     * @throws FormError
     */
    public function saveMotion(Motion $motion, MotionUpdateRequest $dto, array $amendmentOverrides): void
    {
        if (!$this->adminMode && !$this->allowTextEdit) {
            throw new FormError(\Yii::t('motion', 'err_create_permission'));
        }

        $supportForm = $this->motionType->getMotionSupportTypeClass();
        $supportForm->setAdminMode($this->adminMode);

        // 1. Set data, but don't validate yet
        $initiators = $supportForm->getMotionInitiatorsFromDto($this->motionType, $dto->initiators);
        $supporters = $supportForm->getMotionSupportersFromDto($this->motionType, $dto->supporters ?? []);
        $this->supporters = array_merge($initiators, $supporters); // Used by edit form in case validation fails

        if ($dto->agendaItemId !== null) {
            foreach ($this->motionType->agendaItems as $agendaItem) {
                if ($agendaItem->id === $dto->agendaItemId) {
                    $this->agendaItem = $agendaItem;
                }
            }
        }

        if ($this->motionType->getConsultation()->getSettings()->allowUsersToSetTags || $this->adminMode) {
            $this->tags = $dto->tags ?? [];
        } else {
            foreach ($motion->getPublicTopicTags() as $tag) {
                $this->tags[] = $tag->id;
            }
        }

        // 2. Validate data
        if ($this->allowTextEdit) {
            $this->setAndVerifySectionContent($dto->sections);
        }
        if ($this->allowEditingInitiators) {
            $this->validateInitiators($supportForm, $initiators, $supporters);
        }

        // Save Data
        if (!$motion->save()) {
            throw new FormError(\Yii::t('motion', 'err_create'));
        }
        if ($this->allowEditingInitiators) {
            $supportForm->submitMotion($motion, $this->supporters);
        }

        if ($this->allowTextEdit) {
            $this->overwriteSections($motion);
        }

        $motion->refreshTitle();
        $motion->dateContentModification = date('Y-m-d H:i:s');
        $motion->save();

        if ($this->allowSetTags) {
            $motion->setTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, $this->tags);
        }

        if ($this->allowTextEdit) {
            $this->updateTextRewritingAmendments($motion, $dto->sections, $amendmentOverrides);
        }
    }
}
