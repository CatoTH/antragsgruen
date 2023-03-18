<?php

namespace app\models\forms;

use app\components\RequestContext;
use app\models\settings\PrivilegeQueryContext;
use app\models\settings\Privileges;
use app\models\db\{ConsultationAgendaItem,
    ConsultationMotionType,
    ConsultationSettingsTag,
    Motion,
    MotionSection,
    MotionSupporter,
    User};
use app\models\exceptions\FormError;
use app\models\sectionTypes\ISectionType;
use yii\base\Model;

class MotionEditForm extends Model
{
    public ConsultationMotionType $motionType;
    public ?ConsultationAgendaItem $agendaItem;

    /** @var MotionSupporter[] */
    public array $supporters = [];

    /** @var MotionSection[] */
    public array $sections = [];

    public array $tags = [];
    public ?int $motionId = null;

    /** @var string[] */
    public array $fileUploadErrors = [];

    private bool $adminMode = false;

    public function __construct(ConsultationMotionType $motionType, ?ConsultationAgendaItem $agendaItem, ?Motion $motion)
    {
        parent::__construct();
        $this->motionType = $motionType;
        $this->agendaItem = $agendaItem;
        $this->setSection($motion);
    }


    private function setSection(?Motion $motion): void
    {
        $motionSections = [];
        if ($motion) {
            $this->motionId   = $motion->id;
            $this->supporters = $motion->motionSupporters;
            if ($motion->getMyConsultation()->getSettings()->allowUsersToSetTags) {
                foreach ($motion->getPublicTopicTags() as $tag) {
                    $this->tags[] = $tag->id;
                }
            }
            foreach ($motion->getActiveSections(null, true) as $section) {
                $motionSections[$section->sectionId] = $section;
            }
        }
        $this->sections = [];
        foreach ($this->motionType->motionSections as $sectionType) {
            if (isset($motionSections[$sectionType->id])) {
                $this->sections[] = $motionSections[$sectionType->id];
            } else {
                $section = new MotionSection();
                $section->sectionId = $sectionType->id;
                $section->setData('');
                $section->dataRaw  = '';
                $section->public = $sectionType->getSettingsObj()->public;
                $section->cache = '';
                $section->refresh();
                $this->sections[] = $section;
            }
        }
    }

    public function rules(): array
    {
        return [
            [['id', 'type'], 'number'],
            [['supporters', 'tags'], 'safe'],
        ];
    }

    public function setAdminMode(bool $set): void
    {
        $this->adminMode = $set;
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
     * @param array $data
     * @param bool $safeOnly
     * @throws FormError
     */
    public function setAttributes($data, $safeOnly = true)
    {
        $this->fileUploadErrors = [];

        list($values, $files) = $data;
        parent::setAttributes($values, $safeOnly);

        if (isset($values['agendaItem']) && $values['agendaItem']) {
            foreach ($this->motionType->agendaItems as $agendaItem) {
                if ($agendaItem->id === IntVal($values['agendaItem'])) {
                    $this->agendaItem = $agendaItem;
                }
            }
        }

        foreach ($this->sections as $section) {
            if ($this->motionId && $section->getSettings()->type === ISectionType::TYPE_TEXT_SIMPLE) {
                // Updating the text is done separately, including amendment rewriting
                continue;
            }
            if ($section->getSettings()->type === ISectionType::TYPE_TITLE && isset($values['motion']['title'])) {
                $section->getSectionType()->setMotionData($values['motion']['title']);
            }
            if (isset($values['sectionDelete']) && isset($values['sectionDelete'][$section->sectionId])) {
                if (!$section->getSettings()->required) {
                    $section->getSectionType()->deleteMotionData();
                }
            }
            if (isset($values['sections'][$section->sectionId])) {
                $section->getSectionType()->setMotionData($values['sections'][$section->sectionId]);
            }
            if (isset($files['sections']) && isset($files['sections']['tmp_name'])) {
                if (!empty($files['sections']['tmp_name'][$section->sectionId])) {
                    $data = [];
                    foreach ($files['sections'] as $key => $vals) {
                        if (isset($vals[$section->sectionId])) {
                            $data[$key] = $vals[$section->sectionId];
                        }
                    }
                    $section->getSectionType()->setMotionData($data);
                }
                if (!empty($files['sections']['error'][$section->sectionId])) {
                    $error = $files['sections']['error'][$section->sectionId];
                    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                        $this->fileUploadErrors[] = $section->getSettings()->title . ': Uploaded file is too big';
                    }
                }
            }
        }
    }

    /**
     * @throws FormError
     */
    private function createMotionVerify(): void
    {
        $errors = [];

        foreach ($this->sections as $section) {
            $type = $section->getSettings();
            if ($section->getData() === '' && $type->required) {
                $errors[] = str_replace('%FIELD%', $type->title, \Yii::t('base', 'err_no_data_given'));
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%MAX%', $type->title, \Yii::t('base', 'err_max_len_exceed'));
            }
        }
        $errors = array_merge($errors, $this->fileUploadErrors);

        try {
            $this->motionType->getMotionSupportTypeClass()->validateMotion();
        } catch (FormError $e) {
            $errors = array_merge($errors, $e->getMessages());
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

    /**
     * @throws FormError
     */
    public function createMotion(): Motion
    {
        $consultation = $this->motionType->getConsultation();

        if (!$this->motionType->getMotionPolicy()->checkCurrUserMotion()) {
            throw new FormError(\Yii::t('motion', 'err_create_permission'));
        }

        $motion = new Motion();

        $this->setAttributes([RequestContext::getWebApplication()->request->post(), $_FILES]);
        $this->supporters = $this->motionType->getMotionSupportTypeClass()->getMotionSupporters($motion);

        $this->createMotionVerify();

        $motion->status = Motion::STATUS_DRAFT;
        $motion->consultationId = $this->motionType->consultationId;
        $motion->textFixed = ($consultation->getSettings()->adminsMayEdit ? 0 : 1);
        $motion->title = '';
        $motion->titlePrefix = '';
        $motion->version = Motion::VERSION_DEFAULT;
        $motion->dateCreation = date('Y-m-d H:i:s');
        $motion->dateContentModification = date('Y-m-d H:i:s');
        $motion->motionTypeId = $this->motionType->id;
        $motion->cache = '';
        $motion->agendaItemId = ($this->agendaItem ? $this->agendaItem->id : null);

        if ($motion->save()) {
            $this->motionType->getMotionSupportTypeClass()->submitMotion($motion);

            if ($motion->getMyConsultation()->getSettings()->allowUsersToSetTags) {
                foreach ($this->tags as $tagId) {
                    /** @var ConsultationSettingsTag $tag */
                    $tag = ConsultationSettingsTag::findOne(['id' => $tagId, 'consultationId' => $consultation->id]);
                    if ($tag) {
                        $motion->link('tags', $tag);
                    }
                }
            }

            foreach ($this->sections as $section) {
                $section->motionId = $motion->id;
                $section->save();
            }

            $motion->refreshTitle();
            $motion->slug = $motion->createSlug();
            $motion->save();

            return $motion;
        } else {
            throw new FormError(\Yii::t('motion', 'err_create'));
        }
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
            if ($section->getData() === '' && $type->required) {
                $errors[] = str_replace('%FIELD%', $type->title, \Yii::t('base', 'err_no_data_given'));
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%MAX%', $type->title, \Yii::t('base', 'err_max_len_exceed'));
            }
        }
        $errors = array_merge($errors, $this->fileUploadErrors);

        $this->motionType->getMotionSupportTypeClass()->validateMotion();

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
        if (!$this->adminMode) {
            $this->saveMotionVerify();

            if (!$motion->canEdit()) {
                throw new FormError(\Yii::t('motion', 'err_create_permission'));
            }
        }

        if (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_INITIATORS, PrivilegeQueryContext::motion($motion))) {
            $this->supporters = $this->motionType->getMotionSupportTypeClass()->getMotionSupporters($motion);
        }

        if ($motion->save()) {
            if (!$this->adminMode || User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_INITIATORS, PrivilegeQueryContext::motion($motion))) {
                $this->motionType->getMotionSupportTypeClass()->submitMotion($motion);
            }

            if ($motion->getMyConsultation()->getSettings()->allowUsersToSetTags) {
                // Tags
                foreach ($motion->getPublicTopicTags() as $tag) {
                    $motion->unlink('tags', $tag, true);
                }
                foreach ($this->tags as $tagId) {
                    /** @var ConsultationSettingsTag $tag */
                    $tag = ConsultationSettingsTag::findOne(['id' => $tagId, 'consultationId' => $consultation->id]);
                    if ($tag) {
                        $motion->link('tags', $tag);
                    }
                }
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
