<?php

namespace app\models\forms;

use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\AmendmentSection;
use app\models\db\AmendmentSupporter;
use app\models\exceptions\FormError;

class AmendmentEditForm extends \yii\base\Model
{
    /** @var Motion */
    public $motion;

    /** @var AmendmentSupporter[] */
    public $supporters = array();

    /** @var AmendmentSection[] */
    public $sections = array();

    /** @var null|int */
    public $amendmentId = null;

    /**
     * @param Motion $motion
     * @param null|Amendment $amendment
     */
    public function __construct(Motion $motion, $amendment)
    {
        parent::__construct();
        $this->motion      = $motion;
        /** @var AmendmentSection[] $amendmentSections */
        $amendmentSections = [];
        $motionSections = [];
        foreach ($motion->sections as $section) {
            $motionSections[$section->sectionId] = $section;
        }
        if ($amendment) {
            $this->amendmentId = $amendment->id;
            $this->supporters  = $amendment->amendmentSupporters;
            foreach ($amendment->sections as $section) {
                $amendmentSections[$section->sectionId] = $section;
                if ($section->data == '') {
                    $data = $motionSections[$section->sectionId]->data;
                    $amendmentSections[$section->sectionId]->data = $data;
                    $amendmentSections[$section->sectionId]->dataRaw = $data;
                }
            }
        }
        $this->sections = [];
        foreach ($motion->consultation->motionSections as $sectionType) {
            if (!$sectionType->hasAmendments) {
                continue;
            }
            if (isset($amendmentSections[$sectionType->id])) {
                $this->sections[] = $amendmentSections[$sectionType->id];
            } else {
                if (isset($motionSections[$sectionType->id])) {
                    $data = $motionSections[$sectionType->id]->data;
                } else {
                    $data = '';
                }
                $section            = new AmendmentSection();
                $section->sectionId = $sectionType->id;
                $section->data      = $data;
                $section->dataRaw   = $data;
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
            [['type'], 'required'],
            [['id', 'type'], 'number'],
            [
                'type', 'required', 'message' => 'Du musst einen Typ angeben.'
            ],
            [['supporters', 'tags', 'type'], 'safe'],
        ];
    }

    /**
     * @param array $values
     * @param array $files
     * @param bool $safeOnly
     */
    public function setAttributes($values, $files, $safeOnly = true)
    {
        parent::setAttributes($values, $safeOnly);
        foreach ($this->sections as $section) {
            if (isset($values['sections'][$section->consultationSetting->id])) {
                $section->getSectionType()->setData($values['sections'][$section->consultationSetting->id]);
            }
            if (isset($files['sections']) && isset($files['sections']['tmp_name'])) {
                if (!empty($files['sections']['tmp_name'][$section->consultationSetting->id])) {
                    $data = array();
                    foreach ($files['sections'] as $key => $vals) {
                        if (isset($vals[$section->consultationSetting->id])) {
                            $data[$key] = $vals[$section->consultationSetting->id];
                        }
                    }
                    $section->getSectionType()->setData($data);
                }
            }
        }
    }
}
