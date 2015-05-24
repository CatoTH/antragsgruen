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
    public $supporters = array();

    /** @var AmendmentSection[] */
    public $sections = array();

    /** @var null|int */
    public $amendmentId = null;

    /** @var string */
    public $reason = '';

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
            foreach ($amendment->sections as $section) {
                $amendmentSections[$section->sectionId] = $section;
                if ($section->data == '') {
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
     * @param array $data
     * @param bool $safeOnly
     */
    public function setAttributes($data, $safeOnly = true)
    {
        list($values, $files) = $data;
        parent::setAttributes($values, $safeOnly);
        foreach ($this->sections as $section) {
            if (isset($values['sections'][$section->consultationSetting->id])) {
                $section->getSectionType()->setAmendmentData($values['sections'][$section->consultationSetting->id]);
            }
            if (isset($files['sections']) && isset($files['sections']['tmp_name'])) {
                if (!empty($files['sections']['tmp_name'][$section->consultationSetting->id])) {
                    $data = array();
                    foreach ($files['sections'] as $key => $vals) {
                        if (isset($vals[$section->consultationSetting->id])) {
                            $data[$key] = $vals[$section->consultationSetting->id];
                        }
                    }
                    $section->getSectionType()->setAmendmentData($data);
                }
            }
        }
        $this->reason = HTMLTools::cleanSimpleHtml($values['amendmentReason']);
    }


    /**
     * @throws FormError
     */
    private function createAmendmentVerify()
    {
        $errors = [];

        foreach ($this->sections as $section) {
            $type = $section->consultationSetting;
            if ($section->data == '' && $type->required) {
                $errors[] = 'Keine Daten angegeben (Feld: ' . $type->title . ')';
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%max%', $type->maxLen, 'Maximum length of %max% exceeded');
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
        if (!$this->motion->motionType->getMotionPolicy()->checkAmendmentSubmit()) {
            throw new FormError("Keine Berechtigung zum Anlegen von Änderungsanträgen.");
        }

        $amendment = new Amendment();

        $this->setAttributes([$_POST, $_FILES]);
        $this->supporters = $this->motion->motionType->getAmendmentInitiatorFormClass()
            ->getAmendmentSupporters($amendment);

        $this->createAmendmentVerify();

        $amendment->status            = Motion::STATUS_DRAFT;
        $amendment->statusString      = '';
        $amendment->motionId          = $this->motion->id;
        $amendment->textFixed         = ($this->motion->consultation->getSettings()->adminsMayEdit ? 0 : 1);
        $amendment->titlePrefix       = '';
        $amendment->dateCreation      = date("Y-m-d H:i:s");
        $amendment->changeMetatext    = ''; // @TODO
        $amendment->changeText        = ''; // @TODO
        $amendment->changeExplanation = $this->reason;
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
            throw new FormError("Ein Fehler beim Anlegen ist aufgetreten");
        }
    }


    /**
     * @throws FormError
     */
    private function saveAmendmentVerify()
    {
        $errors = [];

        foreach ($this->sections as $section) {
            $type = $section->consultationSetting;
            if ($section->data == '' && $type->required) {
                $errors[] = 'Keine Daten angegeben (Feld: ' . $type->title . ')';
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%max%', $type->maxLen, 'Maximum length of %max% exceeded');
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
        if (!$motionType->getAmendmentPolicy()->checkAmendmentSubmit()) {
            throw new FormError("Keine Berechtigung zum Anlegen von Änderungsanträgen.");
        }

        $this->saveAmendmentVerify();
        $amendment->changeExplanation = $this->reason;

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
            throw new FormError("Ein Fehler beim Anlegen ist aufgetreten");
        }
    }
}
