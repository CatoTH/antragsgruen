<?php

namespace app\models\forms;

use app\components\HTMLTools;
use app\models\settings\{AntragsgruenApp, PrivilegeQueryContext, Privileges};
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

    /** @var string[] */
    public array $fileUploadErrors = [];

    private bool $adminMode = false;
    private bool $allowEditingInitiators = true; // Only affects updating

    public function __construct(
        public readonly ConsultationMotionType $motionType,
        public ?ConsultationAgendaItem $agendaItem
    ) {
    }

    /**
     * @throws Internal
     * @throws \app\models\exceptions\NotFound
     * @return array{ConsultationMotionType, ConsultationAgendaItem|null}
     */
    public static function getMotionTypeForCreate(Consultation $consultation, int $motionTypeId = 0, int $agendaItemId = 0, int $cloneFrom = 0): array
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
        $fileUploadErrors = []; // @TODO
        foreach ($this->sections as $section) {
            $sectionSettings = $section->getSettings();
            $requestSection = $requestSectionsById[$section->sectionId] ?? null;
            if ($requestSection === null) {
                continue;
            }
            if ($requestSection->getFileData() !== null) {
                $section->getSectionType()->setMotionData($requestSection->getFileData());
                if (!empty($requestSection->getFileData()['error'])) {
                    $error = $requestSection->getFileData()['error'];
                    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                        $fileUploadErrors[] = $sectionSettings->title . ': Uploaded file is too big';
                    }
                }
            } elseif ($requestSection->data !== null) {
                $section->getSectionType()->setMotionData($requestSection->data);
            } else {
                $section->getSectionType()->deleteMotionData();
            }

            if ($section->getData() === '' && $sectionSettings->required === ConsultationSettingsMotionSection::REQUIRED_YES) {
                $errors[] = str_replace('%FIELD%', $sectionSettings->title, \Yii::t('base', 'err_no_data_given'));
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%MAX%', $sectionSettings->title, \Yii::t('base', 'err_max_len_exceed'));
            }
        }

        $errors = array_merge($errors, $fileUploadErrors);
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
     * Returns true, if the rewriting was successful
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
    public function createMotion(MotionCreateRequest $dto): Motion
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
        if ($this->motionType->getConsultation()->getSettings()->allowUsersToSetTags || $this->adminMode) {
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

        if ($dto->tags && ($consultation->getSettings()->allowUsersToSetTags || $this->adminMode)) {
            $motion->setTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, $dto->tags);
        }

        foreach ($this->sections as $section) {
            $section->motionId = $motion->id;
            $section->save();
        }

        $motion->refreshTitle();
        $motion->slug = $motion->createSlug();
        $motion->save();

        return $motion;
    }

    /**
     * @throws FormError
     */
    public function saveMotion(Motion $motion, MotionUpdateRequest $dto, array $amendmentOverrides): void
    {
        if (!$this->adminMode && !$motion->canEditText()) {
            throw new FormError(\Yii::t('motion', 'err_create_permission'));
        }

        $consultation = $this->motionType->getConsultation();
        $supportForm = $this->motionType->getMotionSupportTypeClass();

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

        // Initiators are only edited (validated and persisted) when the user is allowed to; otherwise the existing ones are kept.
        $editingInitiators = $this->allowEditingInitiators
            && (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_INITIATORS, PrivilegeQueryContext::motion($motion)));

        // In admin mode, editing the motion text (including rewriting affected amendments) requires PRIVILEGE_MOTION_TEXT_EDIT.
        // Users editing their own motion are already gated by the canEditText() check above.
        $mayEditText = !$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_TEXT_EDIT, PrivilegeQueryContext::motion($motion));

        // 2. Validate data
        $this->setAndVerifySectionContent($dto->sections);
        if ($editingInitiators) {
            $this->validateInitiators($supportForm, $initiators, $supporters);
        }

        // Save Data
        if (!$motion->save()) {
            throw new FormError(\Yii::t('motion', 'err_create'));
        }
        if ($editingInitiators) {
            $supportForm->submitMotion($motion, $this->supporters);
        }

        if ($mayEditText) {
            $this->overwriteSections($motion);
        }

        $motion->refreshTitle();
        $motion->dateContentModification = date('Y-m-d H:i:s');
        $motion->save();

        $motion->setTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, $this->tags);

        if ($mayEditText) {
            $this->updateTextRewritingAmendments($motion, $dto->sections, $amendmentOverrides);
        }
    }
}
