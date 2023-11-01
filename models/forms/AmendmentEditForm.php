<?php

namespace app\models\forms;

use app\components\HTMLTools;
use app\models\db\{Amendment,
    ConsultationAgendaItem,
    ConsultationSettingsTag,
    Motion,
    AmendmentSection,
    AmendmentSupporter,
    User};
use app\components\RequestContext;
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

    public function setAttributes(array $values, array $files): void
    {
        $consultation = $this->motion->getMyConsultation();
        if (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_TEXT_EDIT, PrivilegeQueryContext::motion($this->motion))) {
            foreach ($this->sections as $section) {
                if (isset($values['sections'][$section->getSettings()->id])) {
                    $sectionType = $section->getSectionType();
                    if ($this->adminMode && $section->getSettings() && $section->getSettings()->type === ISectionType::TYPE_TEXT_SIMPLE) {
                        /** @var TextSimple $sectionType */
                        $sectionType->forceMultipleParagraphMode(true);
                    }
                    $sectionType->setAmendmentData($values['sections'][$section->getSettings()->id]);
                }
                if (isset($files['sections']['tmp_name'])) {
                    if (!empty($files['sections']['tmp_name'][$section->getSettings()->id])) {
                        $data = [];
                        foreach ($files['sections'] as $key => $vals) {
                            if (isset($vals[$section->getSettings()->id])) {
                                $data[$key] = $vals[$section->getSettings()->id];
                            }
                        }
                        $section->getSectionType()->setAmendmentData($data);
                    }
                }
            }
            if (isset($values['amendmentReason'])) {
                $this->reason = HTMLTools::cleanSimpleHtml($values['amendmentReason']);
            }
            if (isset($values['amendmentEditorial'])) {
                $this->editorial = HTMLTools::cleanSimpleHtml($values['amendmentEditorial']);
            } else {
                $this->editorial = '';
            }

            $this->globalAlternative = (isset($values['globalAlternative']) && $consultation->getSettings()->globalAlternatives);
        }

        if (isset($values['createFromAmendment']) && $this->motion->getMyMotionType()->getSettingsObj()->allowAmendmentsToAmendments) {
            $baseAmendment = $consultation->getAmendment((int)$values['createFromAmendment']);
            if ($baseAmendment && $baseAmendment->motionId === $this->motion->id) {
                $this->toAnotherAmendment = $baseAmendment->id;
            }
        }

        if ($this->motion->getMyConsultation()->getSettings()->allowUsersToSetTags || $this->adminMode) {
            $this->tags = array_map(fn (string $id): int => intval($id), $values['tags'] ?? []);
        }
    }


    /**
     * @throws FormError
     */
    private function createAmendmentVerify(): void
    {
        $errors = [];

        foreach ($this->sections as $section) {
            $type = $section->getSettings();
            if ($section->data == '' && $type->required) {
                $errors[] = str_replace('%FIELD%', $type->title, \Yii::t('base', 'err_no_data_given'));
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%MAX%', (string)$type->maxLen, \Yii::t('base', 'err_max_len_exceed'));
            }
        }

        try {
            $this->motion->getMyMotionType()->getAmendmentSupportTypeClass()->validateAmendment();
        } catch (FormError $e) {
            $errors = array_merge($errors, $e->getMessages());
        }

        if (count($errors) > 0) {
            throw new FormError($errors);
        }
    }

    /**
     * @throws FormError
     * @throws \Throwable
     * @throws \app\models\exceptions\NotAmendable
     */
    public function createAmendment(): Amendment
    {
        $consultation = $this->motion->getMyConsultation();

        if (!$this->motion->isCurrentlyAmendable()) {
            throw new FormError(\Yii::t('amend', 'err_create_permission'));
        }

        $amendment = new Amendment();

        $this->setAttributes(RequestContext::getWebApplication()->request->post(), $_FILES);
        $this->supporters = $this->motion->motionType->getAmendmentSupportTypeClass()
            ->getAmendmentSupporters($amendment);

        $this->createAmendmentVerify();

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
            $this->motion->motionType->getAmendmentSupportTypeClass()->submitAmendment($amendment);

            if ($this->motion->getMyConsultation()->getSettings()->allowUsersToSetTags || $this->adminMode) {
                $amendment->setTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, $this->tags);
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
     */
    private function saveAmendmentVerify(): void
    {
        $errors = [];

        foreach ($this->sections as $section) {
            $type = $section->getSettings();
            if ($section->data == '' && $type->required) {
                $errors[] = str_replace('%FIELD%', $type->title, \Yii::t('base', 'err_no_data_given'));
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%MAX%', (string)$type->maxLen, \Yii::t('base', 'err_max_len_exceed'));
            }
        }

        if ($this->allowEditingInitiators) {
            $this->motion->getMyMotionType()->getAmendmentSupportTypeClass()->validateAmendment();
        }

        if (count($errors) > 0) {
            throw new FormError(implode("\n", $errors));
        }
    }


    /**
     * @throws \Throwable
     * @throws \app\models\exceptions\NotAmendable
     */
    public function saveAmendment(Amendment $amendment): void
    {
        $consultation = $this->motion->getMyConsultation();
        $ctx = PrivilegeQueryContext::amendment($amendment);

        if ($this->allowEditingInitiators && (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_INITIATORS, $ctx))) {
            $this->supporters = $this->motion->getMyMotionType()->getAmendmentSupportTypeClass()->getAmendmentSupporters($amendment);
        }

        if (!$this->adminMode) {
            if (!$amendment->canEditText()) {
                throw new FormError(\Yii::t('amend', 'err_create_permission'));
            }

            $this->saveAmendmentVerify();
        }

        if (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_TEXT_EDIT, $ctx)) {
            $amendment->changeExplanation = $this->reason;
            $amendment->changeEditorial = $this->editorial;
            $amendment->globalAlternative = ($this->globalAlternative ? 1 : 0);
        }

        if ($amendment->save()) {
            $motionType = $this->motion->getMyMotionType();

            if ($this->allowEditingInitiators && (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_INITIATORS, $ctx))) {
                $motionType->getAmendmentSupportTypeClass()->submitAmendment($amendment);
            }

            if ($amendment->getMyConsultation()->getSettings()->allowUsersToSetTags || $this->adminMode) {
                $amendment->setTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, $this->tags);
            }

            if (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_TEXT_EDIT, $ctx)) {
                // Sections
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
