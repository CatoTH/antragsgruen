<?php

namespace app\models\forms;

use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsTag;
use app\models\db\ConsultationText;
use app\models\db\ConsultationUserPrivilege;
use app\models\exceptions\FormError;
use yii\base\Model;

class ConsultationCreateForm extends Model
{
    /** @var string */
    public $urlPath;
    public $title;
    public $titleShort;

    /** @var Consultation */
    public $template = null;

    /** @var boolean */
    public $setAsDefault = true;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['urlPath', 'title', 'titleShort', 'template'], 'required'],
            [['setAsDefault'], 'boolean'],
            [['urlPath', 'title', 'titleShort', 'setAsDefault'], 'safe'],
        ];
    }

    /**
     * @throws FormError
     */
    public function createConsultation()
    {
        if ($this->title == '' || $this->titleShort == '' || $this->urlPath == '') {
            throw new FormError('Bitte fülle alle Felder aus');
        }
        foreach ($this->template->site->consultations as $cons) {
            if (mb_strtolower($cons->urlPath) == mb_strtolower($this->urlPath)) {
                $msg = 'Diese Adresse ist leider schon von einer anderen Veranstaltung auf dieser Seite vergeben.';
                throw new FormError($msg);
            }
        }

        $consultation                     = new Consultation();
        $consultation->siteId             = $this->template->siteId;
        $consultation->type               = $this->template->type;
        $consultation->amendmentNumbering = $this->template->amendmentNumbering;
        $consultation->urlPath            = $this->urlPath;
        $consultation->title              = $this->title;
        $consultation->titleShort         = $this->titleShort;
        $consultation->wordingBase        = $this->template->wordingBase;
        $consultation->adminEmail         = $this->template->adminEmail;
        $consultation->settings           = $this->template->settings;
        if (!$consultation->save()) {
            throw new FormError($consultation->getErrors());
        }

        foreach ($this->template->motionTypes as $motionType) {
            $newType = new ConsultationMotionType();
            $newType->setAttributes($motionType->getAttributes(), false);
            $newType->consultationId = $consultation->id;
            $newType->id = null;
            if (!$newType->save()) {
                throw new FormError($consultation->getErrors());
            }
        }

        foreach ($this->template->texts as $text) {
            $newText = new ConsultationText();
            $newText->setAttributes($text->getAttributes(), false);
            $newText->consultationId = $consultation->id;
            $newText->id = null;
            if (!$newText->save()) {
                throw new FormError($consultation->getErrors());
            }
        }

        foreach ($this->template->tags as $tag) {
            $newTag = new ConsultationSettingsTag();
            $newTag->setAttributes($tag->getAttributes(), false);
            $newTag->consultationId = $consultation->id;
            $newTag->id = null;
            if (!$newTag->save()) {
                throw new FormError($consultation->getErrors());
            }
        }

        foreach ($this->template->userPrivileges as $priv) {
            $newPriv = new ConsultationUserPrivilege();
            $newPriv->setAttributes($priv->getAttributes(), false);
            $newPriv->consultationId = $consultation->id;
            if (!$newPriv->save()) {
                throw new FormError($consultation->getErrors());
            }
        }

        if ($this->setAsDefault) {
            $this->template->site->currentConsultationId = $consultation->id;
            $this->template->site->save();
        }
    }
}
