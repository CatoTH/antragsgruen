<?php

namespace app\models\forms;

use app\models\db\Consultation;
use app\models\db\ConsultationSettingsTag;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\exceptions\FormError;

class MotionEditForm extends \yii\base\Model
{
    public $error;

    /** @var Consultation */
    private $consultation;

    /** @var array */
    public $supporters = array();
    public $tags       = array();
    public $texts      = array();

    /** @var null|int */
    public $motionId = null;

    public $title;

    /**
     * @param Consultation $consultation
     * @param null|Motion $motion
     */
    public function __construct(Consultation $consultation, $motion)
    {
        $this->consultation = $consultation;
        if ($motion) {
            //  @TODO Werte von $motion
            $this->motionId = $motion->id;
        }
    }


    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['title', 'texts'], 'required'],
            [['id'], 'number'],
            [
                'title', 'required', 'message' => 'Du musst einen Titel angeben.'
            ],
            [['supporters', 'tags', 'texts', 'title'], 'safe'],
        ];
    }

    /**
     * @throws FormError
     * @return Motion
     */
    public function createMotion()
    {
        if (!$this->consultation->getMotionPolicy()->checkMotionSubmit()) {
            $this->error = "Keine Berechtigung zum Anlegen von Anträgen.";
            throw new FormError($this->error);
        }

        foreach ($this->consultation->motionSections as $sectionType) {
            /* @TODO Länge etc. prüfen */
            if (!isset($this->texts[$sectionType->id])) {
                $this->error = "Es fehlt: " . $sectionType->title;
                throw new FormError($this->error);
            }
        }

        $this->consultation->getMotionInitiatorFormClass()->validateInitiatorViewMotion();

        $motion                 = new Motion();
        $motion->status         = Motion::STATUS_DRAFT;
        $motion->consultationId = $this->consultation->id;
        $motion->textFixed      = ($this->consultation->getSettings()->adminsMayEdit ? 0 : 1);
        $motion->title          = $this->title;
        $motion->dateCreation   = date("Y-m-d H:i:s");

        if ($motion->save()) {
            $this->consultation->getMotionInitiatorFormClass()->submitInitiatorViewMotion($motion);

            foreach ($this->tags as $tagId) {
                /** @var ConsultationSettingsTag $tag */
                $tag = ConsultationSettingsTag::findOne(['id' => $tagId, 'consultationId' => $this->consultation->id]);
                if ($tag) {
                    $motion->link('tags', $tag);
                }
            }

            foreach ($this->consultation->motionSections as $sectionType) {
                $section            = new MotionSection();
                $section->sectionId = $sectionType->id;
                $section->motionId  = $motion->id;
                $section->data      = $this->texts[$sectionType->id];
                $section->save();
            }

            return $motion;
        } else {
            var_dump($motion->getErrors());
            $this->error = "Ein Fehler beim Anlegen ist aufgetreten";
            throw new FormError($this->error);
        }
    }
}
