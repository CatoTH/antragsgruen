<?php

namespace app\models\forms;

use app\components\HTMLTools;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\AmendmentSection;
use app\models\db\AmendmentSupporter;
use app\models\exceptions\FormError;
use yii\base\Model;

class AmendmentEditForm extends Model
{
    /** @var Motion */
    public $motion;

    /** @var AmendmentSupporter[] */
    public $supporters = [];

    /** @var AmendmentSection[] */
    public $sections = [];

    /** @var null|int */
    public $amendmentId = null;

    /** @var string */
    public $reason = '';

    /** @var string */
    public $editorial = '';

    private $adminMode = false;

    /**
     * @param Motion $motion
     * @param null|Amendment $amendment
     */
    public function __construct(Motion $motion, $amendment)
    {
        parent::__construct();
        $this->motion = $motion;
        /** @var AmendmentSection[] $amendmentSections */
        $amendmentSections = [];
        $motionSections    = [];
        foreach ($motion->sections as $section) {
            $motionSections[$section->sectionId] = $section;
        }
        if ($amendment) {
            $this->amendmentId = $amendment->id;
            $this->supporters  = $amendment->amendmentSupporters;
            $this->reason      = $amendment->changeExplanation;
            $this->editorial   = $amendment->changeEditorial;
            foreach ($amendment->sections as $section) {
                $amendmentSections[$section->sectionId] = $section;
                if ($section->data == '' && isset($motionSections[$section->sectionId])) {
                    $data                                            = $motionSections[$section->sectionId]->data;
                    $amendmentSections[$section->sectionId]->data    = $data;
                    $amendmentSections[$section->sectionId]->dataRaw = $data;
                }
            }
        }
        $this->sections = [];
        foreach ($motion->motionType->motionSections as $sectionType) {
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
                $section            = new AmendmentSection();
                $section->sectionId = $sectionType->id;
                $section->data      = $data;
                $section->dataRaw   = $data;
                $section->cache     = '';
                $section->refresh();
                if ($origSection) {
                    $section->setOriginalMotionSection($origSection);
                }
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
            [['type'], 'required'],
            [['id', 'type'], 'number'],
            [
                'type', 'required', 'message' => \Yii::t('amend', 'err_type_missing')
            ],
            [['supporters', 'tags', 'type'], 'safe'],
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
     * @param Amendment $amendment
     */
    public function cloneSupporters(Amendment $amendment)
    {
        foreach ($amendment->amendmentSupporters as $supp) {
            $suppNew = new AmendmentSupporter();
            $suppNew->setAttributes($supp->getAttributes());
            $this->supporters[] = $suppNew;
        }
    }

    /**
     * @param array $data
     * @param bool $safeOnly
     */
    public function setAttributes($data, $safeOnly = true)
    {
        list($values, $files) = $data;
        parent::setAttributes($values, $safeOnly);

        foreach ($this->sections as $section) {
            if (isset($values['sections'][$section->getSettings()->id])) {
                $section->getSectionType()->setAmendmentData($values['sections'][$section->getSettings()->id]);
            }
            if (isset($files['sections']) && isset($files['sections']['tmp_name'])) {
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
        }
    }


    /**
     * @throws FormError
     */
    private function createAmendmentVerify()
    {
        $errors = [];

        foreach ($this->sections as $section) {
            $type = $section->getSettings();
            if ($section->data == '' && $type->required) {
                $errors[] = str_replace('%FIELD%', $type->title, \Yii::t('base', 'err_no_data_given'));
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%MAX%', $type->maxLen, \Yii::t('base', 'err_max_len_exceed'));
            }
        }

        try {
            $this->motion->motionType->getAmendmentInitiatorFormClass()->validateAmendment();
        } catch (FormError $e) {
            $errors = array_merge($errors, $e->getMessages());
        }

        if (count($errors) > 0) {
            throw new FormError($errors);
        }
    }

    /**
     * @throws FormError
     * @return Amendment
     */
    public function createAmendment()
    {
        if (!$this->motion->motionType->getAmendmentPolicy()->checkCurrUserAmendment()) {
            throw new FormError(\Yii::t('amend', 'err_create_permission'));
        }

        $amendment = new Amendment();

        $this->setAttributes([$_POST, $_FILES]);
        $this->supporters = $this->motion->motionType->getAmendmentInitiatorFormClass()
            ->getAmendmentSupporters($amendment);

        $this->createAmendmentVerify();

        $amendment->status            = Motion::STATUS_DRAFT;
        $amendment->statusString      = '';
        $amendment->motionId          = $this->motion->id;
        $amendment->textFixed         = ($this->motion->getConsultation()->getSettings()->adminsMayEdit ? 0 : 1);
        $amendment->titlePrefix       = '';
        $amendment->dateCreation      = date('Y-m-d H:i:s');
        $amendment->changeEditorial   = $this->editorial;
        $amendment->changeExplanation = $this->reason;
        $amendment->changeText        = '';
        $amendment->cache             = '';

        if ($amendment->save()) {
            $this->motion->motionType->getAmendmentInitiatorFormClass()->submitAmendment($amendment);

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
    private function saveAmendmentVerify()
    {
        $errors = [];

        foreach ($this->sections as $section) {
            $type = $section->getSettings();
            if ($section->data == '' && $type->required) {
                $errors[] = str_replace('%FIELD%', $type->title, \Yii::t('base', 'err_no_data_given'));
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%MAX%', $type->maxLen, \Yii::t('base', 'err_max_len_exceed'));
            }
        }

        $this->motion->motionType->getAmendmentInitiatorFormClass()->validateAmendment();

        if (count($errors) > 0) {
            throw new FormError(implode("\n", $errors));
        }
    }


    /**
     * @param Amendment $amendment
     * @throws FormError
     */
    public function saveAmendment(Amendment $amendment)
    {
        $motionType = $this->motion->motionType;
        if (!$motionType->getAmendmentPolicy()->checkCurrUserAmendment()) {
            throw new FormError(\Yii::t('amend', 'err_create_permission'));
        }

        $this->supporters = $this->motion->motionType
            ->getAmendmentInitiatorFormClass()->getAmendmentSupporters($amendment);

        if (!$this->adminMode) {
            $this->saveAmendmentVerify();
        }
        $amendment->changeExplanation = $this->reason;
        $amendment->changeEditorial   = $this->editorial;

        if ($amendment->save()) {
            $motionType->getAmendmentInitiatorFormClass()->submitAmendment($amendment);

            // Sections
            foreach ($amendment->sections as $section) {
                $section->delete();
            }
            foreach ($this->sections as $section) {
                $section->amendmentId = $amendment->id;
                $section->save();
            }

            $amendment->save();
        } else {
            throw new FormError(\Yii::t('base', 'err_unknown'));
        }
    }
}
