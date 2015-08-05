<?php

namespace app\models\forms;

use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsTag;
use app\models\db\ConsultationText;
use app\models\db\ConsultationUserPrivilege;
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
     */
    public function createConsultation()
    {
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
            var_dump($consultation->getErrors());
            return;
        }

        foreach ($this->template->motionTypes as $motionType) {
            $newType = new ConsultationMotionType();
            $newType->setAttributes($motionType->getAttributes(), false);
            $newType->consultationId = $consultation->id;
            $newType->id = null;
            if (!$newType->save()) {
                var_dump($newType->getErrors());
                return;
            }
        }

        foreach ($this->template->texts as $text) {
            $newText = new ConsultationText();
            $newText->setAttributes($text->getAttributes(), false);
            $newText->consultationId = $consultation->id;
            $newText->id = null;
            if (!$newText->save()) {
                var_dump($newText->getErrors());
                return;
            }
        }

        foreach ($this->template->tags as $tag) {
            $newTag = new ConsultationSettingsTag();
            $newTag->setAttributes($tag->getAttributes(), false);
            $newTag->consultationId = $consultation->id;
            $newTag->id = null;
            if (!$newTag->save()) {
                var_dump($newTag->getErrors());
                return;
            }
        }

        foreach ($this->template->userPrivileges as $priv) {
            $newPriv = new ConsultationUserPrivilege();
            $newPriv->setAttributes($priv->getAttributes(), false);
            $newPriv->consultationId = $consultation->id;
            if (!$newPriv->save()) {
                var_dump($newPriv->getErrors());
                return;
            }
        }

        if ($this->setAsDefault) {
            $this->template->site->currentConsultationId = $consultation->id;
        }
    }
}
