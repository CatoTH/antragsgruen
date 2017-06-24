<?php

namespace app\models\forms;

use app\models\db\ConsultationAgendaItem;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsTag;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\db\MotionSupporter;
use app\models\exceptions\FormError;
use app\models\sectionTypes\ISectionType;
use yii\base\Model;

class MotionEditForm extends Model
{
    /** @var ConsultationMotionType */
    public $motionType;

    /** @var ConsultationAgendaItem */
    public $agendaItem;

    /** @var MotionSupporter[] */
    public $supporters = [];

    /** @var array */
    public $tags = [];

    /** @var MotionSection[] */
    public $sections = [];

    /** @var null|int */
    public $motionId = null;

    /** @var string[] */
    public $fileUploadErrors = [];

    private $adminMode = false;

    /**
     * @param ConsultationMotionType $motionType
     * @param null|ConsultationAgendaItem
     * @param null|Motion $motion
     */
    public function __construct(ConsultationMotionType $motionType, $agendaItem, $motion)
    {
        parent::__construct();
        $this->motionType = $motionType;
        $this->agendaItem = $agendaItem;
        $this->setSection($motion);
    }


    /**
     * @param Motion|null $motion
     */
    private function setSection($motion)
    {
        $motionSections = [];
        if ($motion) {
            $this->motionId   = $motion->id;
            $this->supporters = $motion->motionSupporters;
            foreach ($motion->tags as $tag) {
                $this->tags[] = $tag->id;
            }
            foreach ($motion->getActiveSections() as $section) {
                $motionSections[$section->sectionId] = $section;
            }
        }
        $this->sections = [];
        foreach ($this->motionType->motionSections as $sectionType) {
            if (isset($motionSections[$sectionType->id])) {
                $this->sections[] = $motionSections[$sectionType->id];
            } else {
                $section            = new MotionSection();
                $section->sectionId = $sectionType->id;
                $section->data      = '';
                $section->dataRaw   = '';
                $section->cache     = '';
                $section->refresh();
                $this->sections[] = $section;
            }
        }
    }


    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['id', 'type'], 'number'],
            [['supporters', 'tags'], 'safe'],
        ];
    }

    /**
     * @param bool $set
     */
    public function setAdminMode($set)
    {
        $this->adminMode = $set;
    }

    /**
     * @param Motion $motion
     */
    public function cloneSupporters(Motion $motion)
    {
        foreach ($motion->motionSupporters as $supp) {
            $suppNew = new MotionSupporter();
            $suppNew->setAttributes($supp->getAttributes());
            $this->supporters[] = $suppNew;
        }
    }

    /**
     * @param Motion $motion
     */
    public function cloneMotionText(Motion $motion)
    {
        /** @var MotionSection[] $byId */
        $byId = [];
        foreach ($motion->getActiveSections() as $section) {
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
     * @param array $data
     * @param bool $safeOnly
     */
    public function setAttributes($data, $safeOnly = true)
    {
        $this->fileUploadErrors = [];

        list($values, $files) = $data;
        parent::setAttributes($values, $safeOnly);
        foreach ($this->sections as $section) {
            if ($this->motionId && $section->getSettings()->type == ISectionType::TYPE_TEXT_SIMPLE) {
                // Updating the text is done separately, including amendment rewriting
                continue;
            }
            if ($section->getSettings()->type == ISectionType::TYPE_TITLE && isset($values['motion']['title'])) {
                $section->getSectionType()->setMotionData($values['motion']['title']);
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
                    if ($error === UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE) {
                        $this->fileUploadErrors[] = $section->getSettings()->title . ': Uploaded file is too big';
                    }
                }
            }
        }
    }

    /**
     * @throws FormError
     */
    private function createMotionVerify()
    {
        $errors = [];

        foreach ($this->sections as $section) {
            $type = $section->getSettings();
            if ($section->data == '' && $type->required) {
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
     * @param Motion $motion
     * @param string[] $newHtmls
     * @param array $overrides
     * @return bool
     */
    public function updateTextRewritingAmendments(Motion $motion, $newHtmls, $overrides = [])
    {
        foreach ($motion->getAmendmentsRelevantForCollissionDetection() as $amendment) {
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

        foreach ($motion->getAmendmentsRelevantForCollissionDetection() as $amendment) {
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
            $section->data = $newHtmls[$section->sectionId];
            $section->save();
        }

        $this->setSection($motion);

        return true;
    }

    /**
     * @param Motion $motion
     * @param string[] $newHtmls
     */
    public function setSectionTextWithoutSaving(Motion $motion, $newHtmls)
    {
        foreach ($motion->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
            $section->data = $newHtmls[$section->sectionId];
        }
    }

    /**
     * @throws FormError
     * @return Motion
     */
    public function createMotion()
    {
        $consultation = $this->motionType->getConsultation();

        if (!$this->motionType->getMotionPolicy()->checkCurrUserMotion()) {
            throw new FormError(\Yii::t('motion', 'err_create_permission'));
        }

        $motion = new Motion();

        $this->setAttributes([\Yii::$app->request->post(), $_FILES]);
        $this->supporters = $this->motionType->getMotionSupportTypeClass()->getMotionSupporters($motion);

        $this->createMotionVerify();

        $motion->status         = Motion::STATUS_DRAFT;
        $motion->consultationId = $this->motionType->consultationId;
        $motion->textFixed      = ($consultation->getSettings()->adminsMayEdit ? 0 : 1);
        $motion->title          = '';
        $motion->titlePrefix    = '';
        $motion->dateCreation   = date('Y-m-d H:i:s');
        $motion->motionTypeId   = $this->motionType->id;
        $motion->cache          = '';
        $motion->agendaItemId   = ($this->agendaItem ? $this->agendaItem->id : null);

        if ($motion->save()) {
            $this->motionType->getMotionSupportTypeClass()->submitMotion($motion);

            foreach ($this->tags as $tagId) {
                /** @var ConsultationSettingsTag $tag */
                $tag = ConsultationSettingsTag::findOne(['id' => $tagId, 'consultationId' => $consultation->id]);
                if ($tag) {
                    $motion->link('tags', $tag);
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
    private function saveMotionVerify()
    {
        $errors = [];

        foreach ($this->sections as $section) {
            $type = $section->getSettings();
            if ($this->motionId && $type->type == ISectionType::TYPE_TEXT_SIMPLE) {
                // Updating the text is done separately, including amendment rewriting
                continue;
            }
            if ($section->data == '' && $type->required) {
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

    /**
     * @param Motion $motion
     */
    private function overwriteSections(Motion $motion)
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
     * @param Motion $motion
     * @throws FormError
     */
    public function saveMotion(Motion $motion)
    {
        $consultation = $this->motionType->getConsultation();
        if (!$this->motionType->getMotionPolicy()->checkCurrUserMotion()) {
            throw new FormError(\Yii::t('motion', 'err_create_permission'));
        }

        $this->supporters = $this->motionType->getMotionSupportTypeClass()->getMotionSupporters($motion);

        if (!$this->adminMode) {
            $this->saveMotionVerify();
        }

        if ($motion->save()) {
            $this->motionType->getMotionSupportTypeClass()->submitMotion($motion);

            // Tags
            foreach ($motion->tags as $tag) {
                $motion->unlink('tags', $tag, true);
            }
            foreach ($this->tags as $tagId) {
                /** @var ConsultationSettingsTag $tag */
                $tag = ConsultationSettingsTag::findOne(['id' => $tagId, 'consultationId' => $consultation->id]);
                if ($tag) {
                    $motion->link('tags', $tag);
                }
            }

            $this->overwriteSections($motion);

            $motion->refreshTitle();
            $motion->save();
        } else {
            throw new FormError(\Yii::t('motion', 'err_create'));
        }
    }
}
