<?php

namespace app\models\forms;

use app\models\db\Consultation;
use app\models\db\ConsultationSettingsTag;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\db\MotionSupporter;
use app\models\exceptions\FormError;

class MotionEditForm extends \yii\base\Model
{
    /** @var Consultation */
    private $consultation;

    /** @var MotionSupporter[] */
    public $supporters = array();

    /** @var array */
    public $tags  = array();
    public $texts = array();

    /** @var null|int */
    public $motionId = null;

    public $title;
    public $type;

    /**
     * @param Consultation $consultation
     * @param null|Motion $motion
     */
    public function __construct(Consultation $consultation, $motion)
    {
        $this->consultation = $consultation;
        if ($motion) {
            $this->motionId   = $motion->id;
            $this->supporters = $motion->motionSupporters;
            $this->title      = $motion->title;
            $this->type       = $motion->motionTypeId;
            foreach ($motion->tags as $tag) {
                $this->tags = $tag->id;
            }
            foreach ($motion->sections as $s) {
                $this->texts[$s->sectionId] = $s->data;
            }
        }
    }


    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['title', 'texts', 'type'], 'required'],
            [['id', 'type'], 'number'],
            [
                'title', 'required', 'message' => 'Du musst einen Titel angeben.'
            ],
            [
                'type', 'required', 'message' => 'Du musst einen Typ angeben.'
            ],
            [['supporters', 'tags', 'texts', 'title', 'type'], 'safe'],
        ];
    }

    /**
     * @throws FormError
     * @return Motion
     */
    public function createMotion()
    {
        if (!$this->consultation->getMotionPolicy()->checkMotionSubmit()) {
            throw new FormError("Keine Berechtigung zum Anlegen von Anträgen.");
        }

        $errors = [];

        /** @var MotionSection[] $sections */
        $sections = [];
        foreach ($this->consultation->motionSections as $sectionType) {
            if (!isset($this->texts[$sectionType->id])) {
                $errors[] = "Es fehlt: " . $sectionType->title;
            } else {
                $section            = new MotionSection();
                $section->sectionId = $sectionType->id;
                $section->data      = $this->texts[$sectionType->id];
                $sections[]         = $section;

                if (!$section->checkLength()) {
                    $errors[] = str_replace('%max%', $sectionType->maxLen, 'Maximum length of %max% exceeded');
                }
            }
        }

        $foundType = false;
        foreach ($this->consultation->motionTypes as $type) {
            if ($type->id == $this->type) {
                $foundType = true;
            }
        }
        if (!$foundType) {
            $errors[] = 'Motion Type not found';
        }

        $this->consultation->getMotionInitiatorFormClass()->validateInitiatorViewMotion();

        if (count($errors) > 0) {
            throw new FormError(implode("\n", $errors));
        }

        $motion                 = new Motion();
        $motion->status         = Motion::STATUS_DRAFT;
        $motion->consultationId = $this->consultation->id;
        $motion->textFixed      = ($this->consultation->getSettings()->adminsMayEdit ? 0 : 1);
        $motion->title          = $this->title;
        $motion->titlePrefix    = '';
        $motion->dateCreation   = date("Y-m-d H:i:s");
        $motion->motionTypeId   = $this->type;

        if ($motion->save()) {
            $this->consultation->getMotionInitiatorFormClass()->submitInitiatorViewMotion($motion);

            foreach ($this->tags as $tagId) {
                /** @var ConsultationSettingsTag $tag */
                $tag = ConsultationSettingsTag::findOne(['id' => $tagId, 'consultationId' => $this->consultation->id]);
                if ($tag) {
                    $motion->link('tags', $tag);
                }
            }

            foreach ($sections as $section) {
                $section->motionId = $motion->id;
                $section->save();
            }

            return $motion;
        } else {
            throw new FormError("Ein Fehler beim Anlegen ist aufgetreten");
        }
    }


    /**
     * @param Motion $motion
     * @throws FormError
     */
    function saveMotion(Motion $motion)
    {
        // @TODO
    }
}
