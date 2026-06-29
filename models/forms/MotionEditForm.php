<?php

namespace app\models\forms;

use app\components\HTMLTools;
use app\components\RequestContext;
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
use app\models\api\imotion\{MotionCreateInitiator, MotionCreateRequest, SupporterType};
use app\models\db\ISupporter;
use app\models\exceptions\FormError;
use app\models\sectionTypes\ISectionType;

class MotionEditForm
{
    public ConsultationMotionType $motionType;
    public ?ConsultationAgendaItem $agendaItem;

    /** @var MotionSupporter[] */
    public array $supporters = [];

    /** @var MotionSection[] */
    public array $sections = [];

    /** @var int[] */
    public array $tags = [];

    public ?int $motionId = null;

    /** @var string[] */
    public array $fileUploadErrors = [];

    private bool $adminMode = false;
    private bool $allowEditingInitiators = true; // Only affects updating

    public function __construct(ConsultationMotionType $motionType, ?ConsultationAgendaItem $agendaItem, ?Motion $motion)
    {
        $this->motionType = $motionType;
        $this->agendaItem = $agendaItem;
        if ($motion) {
            foreach ($motion->getPublicTopicTags() as $tag) {
                $this->tags[] = $tag->id;
            }
        }
        $this->setSection($motion);
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


    private function setSection(?Motion $motion): void
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

    public function setAttributes(MotionCreateRequest $request): void
    {
        $this->fileUploadErrors = [];

        if ($request->agendaItemId !== null) {
            foreach ($this->motionType->agendaItems as $agendaItem) {
                if ($agendaItem->id === $request->agendaItemId) {
                    $this->agendaItem = $agendaItem;
                }
            }
        }

        $requestSectionsById = [];
        foreach ($request->sections as $requestSection) {
            $requestSectionsById[$requestSection->sectionId] = $requestSection;
        }

        foreach ($this->sections as $section) {
            if ($this->motionId && $section->getSettings()->type === ISectionType::TYPE_TEXT_SIMPLE) {
                // Updating the text is done separately, including amendment rewriting
                continue;
            }
            $requestSection = $requestSectionsById[$section->sectionId] ?? null;
            if ($requestSection === null) {
                continue;
            }
            if ($requestSection->deleted) {
                if ($section->getSettings()->required !== ConsultationSettingsMotionSection::REQUIRED_YES) {
                    $section->getSectionType()->deleteMotionData();
                }
            } elseif ($requestSection->fileData !== null) {
                $section->getSectionType()->setMotionData($requestSection->fileData);
                if (!empty($requestSection->fileData['error'])) {
                    $error = $requestSection->fileData['error'];
                    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                        $this->fileUploadErrors[] = $section->getSettings()->title . ': Uploaded file is too big';
                    }
                }
            } elseif ($requestSection->data !== null) {
                $section->getSectionType()->setMotionData($requestSection->data);
            }
        }

        if ($this->motionType->getConsultation()->getSettings()->allowUsersToSetTags || $this->adminMode) {
            $this->tags = $request->tags ?? [];
        }
    }

    /**
     * @throws FormError
     */
    private function verifySections(): void
    {
        $errors = [];
        foreach ($this->sections as $section) {
            $type = $section->getSettings();
            if ($section->getData() === '' && $type->required === ConsultationSettingsMotionSection::REQUIRED_YES) {
                $errors[] = str_replace('%FIELD%', $type->title, \Yii::t('base', 'err_no_data_given'));
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%MAX%', $type->title, \Yii::t('base', 'err_max_len_exceed'));
            }
        }
        $errors = array_merge($errors, $this->fileUploadErrors);
        if (count($errors) > 0) {
            throw new FormError($errors);
        }
    }

    /**
     * @throws FormError
     */
    private function createMotionVerify(): void
    {
        $this->verifySections();

        $errors = [];
        try {
            $this->motionType->getMotionSupportTypeClass()->validateMotion();
        } catch (FormError $e) {
            $errors = $e->getMessages();
        }
        if (count($errors) > 0) {
            throw new FormError($errors);
        }
    }

    /**
     * Returns true, if the rewriting was successful
     *
     * @param string[] $newHtmls
     * @param array $overrides
     */
    public function updateTextRewritingAmendments(Motion $motion, array $newHtmls, array $overrides = []): bool
    {
        foreach ($motion->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
            $forbiddenFormattings = $section->getSettings()->getForbiddenMotionFormattings();
            $newHtmls[$section->sectionId] = HTMLTools::cleanSimpleHtml($newHtmls[$section->sectionId], $forbiddenFormattings);
        }

        foreach ($motion->getAmendmentsRelevantForCollisionDetection() as $amendment) {
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                if (isset($overrides[$amendment->id]) && isset($overrides[$amendment->id][$section->sectionId])) {
                    $section_overrides = $overrides[$amendment->id][$section->sectionId];
                } else {
                    $section_overrides = [];
                }
                if (!$section->canRewrite($newHtmls[$section->sectionId], $section_overrides)) {
                    return false;
                }
            }
        }

        foreach ($motion->getAmendmentsRelevantForCollisionDetection() as $amendment) {
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                if (isset($overrides[$amendment->id]) && isset($overrides[$amendment->id][$section->sectionId])) {
                    $section_overrides = $overrides[$amendment->id][$section->sectionId];
                } else {
                    $section_overrides = [];
                }
                $section->performRewrite($newHtmls[$section->sectionId], $section_overrides);
                $section->save();
            }
        }

        foreach ($motion->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
            $section->setData($newHtmls[$section->sectionId]);
            $section->save();
        }

        $this->setSection($motion);

        return true;
    }

    /**
     * @param string[] $newHtmls
     */
    public function setSectionTextWithoutSaving(Motion $motion, array $newHtmls): void
    {
        foreach ($motion->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
            $section->setData($newHtmls[$section->sectionId]);
        }
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



    public function setInitiatorsFromDto(MotionCreateRequest $dto): void
    {
        $currentUser = User::getCurrentUser();

        $this->supporters = [];
        $position = 0;
        foreach ($dto->initiators as $initiator) {
            $supporter = new MotionSupporter();
            $supporter->role = MotionSupporter::ROLE_INITIATOR;
            $supporter->position = $position;
            $supporter->personType = ($initiator->personType === SupporterType::ORGANIZATION)
                ? ISupporter::PERSON_ORGANIZATION
                : ISupporter::PERSON_NATURAL;
            $supporter->userId = ($position === 0 && $currentUser) ? $currentUser->id : null;
            $position++;
            $supporter->dateCreation = date('Y-m-d H:i:s');

            if ($supporter->personType === ISupporter::PERSON_NATURAL) {
                $supporter->name = $initiator->name;
                $supporter->organization = $initiator->organization ?? '';
                $supporter->contactName = $initiator->contactName ?? '';
            } else {
                $supporter->organization = $initiator->name;
                $supporter->contactName = $initiator->contactName ?? '';
            }
            $supporter->contactEmail = $initiator->contactEmail ?? '';
            $supporter->contactPhone = $initiator->contactPhone ?? '';
            if ($initiator->gender !== null) {
                $supporter->setExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_GENDER, $initiator->gender);
            }
            if ($initiator->resolutionDate !== null) {
                $supporter->resolutionDate = $initiator->resolutionDate;
            }

            $this->supporters[] = $supporter;
        }
    }

    /**
     * @throws FormError
     * @throws \Exception
     */
    public function createMotion(?MotionCreateRequest $dto = null): Motion
    {
        if (!$this->motionType->getMotionPolicy()->checkCurrUserMotion()) {
            throw new FormError(\Yii::t('motion', 'err_create_permission'));
        }

        $consultation = $this->motionType->getConsultation();
        $motion = new Motion();

        if ($dto !== null) {
            $this->setAttributes($dto);
            $this->setInitiatorsFromDto($dto);
            $this->verifySections();
        } else {
            $this->setAttributes(MotionCreateRequest::fromWebRequest(RequestContext::getAllPostVars(), $_FILES, $this->motionType));
            $this->supporters = $this->motionType->getMotionSupportTypeClass()->getMotionSupporters($motion);
            $this->createMotionVerify();
        }

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
        $motion->agendaItemId = ($this->agendaItem ? $this->agendaItem->id : null);

        if (!$motion->save()) {
            throw new FormError(\Yii::t('motion', 'err_create'));
        }

        if ($dto !== null) {
            foreach ($this->supporters as $supporter) {
                $supporter->motionId = $motion->id;
                $supporter->save();
            }
        } else {
            $this->motionType->getMotionSupportTypeClass()->submitMotion($motion);
        }

        if ($consultation->getSettings()->allowUsersToSetTags || $this->adminMode) {
            $motion->setTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, $this->tags);
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
    private function saveMotionVerify(): void
    {
        $errors = [];

        foreach ($this->sections as $section) {
            $type = $section->getSettings();
            if ($this->motionId && $type->type === ISectionType::TYPE_TEXT_SIMPLE) {
                // Updating the text is done separately, including amendment rewriting
                continue;
            }
            if ($section->getData() === '' && $type->required === ConsultationSettingsMotionSection::REQUIRED_YES) {
                $errors[] = str_replace('%FIELD%', $type->title, \Yii::t('base', 'err_no_data_given'));
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%MAX%', $type->title, \Yii::t('base', 'err_max_len_exceed'));
            }
        }
        $errors = array_merge($errors, $this->fileUploadErrors);

        if ($this->allowEditingInitiators) {
            $this->motionType->getMotionSupportTypeClass()->validateMotion();
        }

        if (count($errors) > 0) {
            throw new FormError(implode("\n", $errors));
        }
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
     */
    public function saveMotion(Motion $motion): void
    {
        $consultation = $this->motionType->getConsultation();
        $ctx = PrivilegeQueryContext::motion($motion);

        if (!$this->adminMode) {
            $this->saveMotionVerify();

            if (!$motion->canEditText()) {
                throw new FormError(\Yii::t('motion', 'err_create_permission'));
            }
        }

        if ($this->allowEditingInitiators && (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_INITIATORS, $ctx))) {
            $this->supporters = $this->motionType->getMotionSupportTypeClass()->getMotionSupporters($motion);
        }

        if ($motion->save()) {
            if ($this->allowEditingInitiators && (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_INITIATORS, PrivilegeQueryContext::motion($motion)))) {
                $this->motionType->getMotionSupportTypeClass()->submitMotion($motion);
            }

            if ($motion->getMyConsultation()->getSettings()->allowUsersToSetTags || $this->adminMode) {
                $motion->setTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, $this->tags);
            }

            if (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_TEXT_EDIT, PrivilegeQueryContext::motion($motion))) {
                $this->overwriteSections($motion);
            }

            $motion->refreshTitle();
            $motion->dateContentModification = date('Y-m-d H:i:s');
            $motion->save();
        } else {
            throw new FormError(\Yii::t('motion', 'err_create'));
        }
    }
}
