<?php

namespace app\models\forms;

use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\ConsultationSettingsTag;
use app\models\db\ConsultationText;
use app\models\db\ConsultationUserPrivilege;
use app\models\db\Site;
use app\models\db\User;
use app\models\exceptions\FormError;
use yii\base\Model;

class ConsultationCreateForm extends Model
{
    /** @var Site */
    private $site;

    /** @var string */
    public $settingsType;
    public $urlPath;
    public $title;
    public $titleShort;

    /** @var Consultation */
    public $template = null;

    /** @var boolean */
    public $setAsDefault = true;

    /** @var SiteCreateForm */
    public $siteCreateWizard;

    public function __construct(Site $site, $config = [])
    {
        parent::__construct($config);
        $this->site             = $site;
        $this->siteCreateWizard = new SiteCreateForm();
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['urlPath', 'title', 'titleShort', 'template'], 'required'],
            [['setAsDefault'], 'boolean'],
            [['urlPath', 'title', 'titleShort', 'setAsDefault', 'settingsType'], 'safe'],
        ];
    }

    /**
     * @param array $values
     * @param bool $safeOnly
     */
    public function setAttributes($values, $safeOnly = true)
    {
        parent::setAttributes($values, $safeOnly);

        $this->setAsDefault = isset($values['setStandard']);
    }

    /**
     * @throws FormError
     */
    private function createConsultationFromTemplate()
    {
        $consultation                     = new Consultation();
        $consultation->siteId             = $this->site->id;
        $consultation->amendmentNumbering = $this->template->amendmentNumbering;
        $consultation->urlPath            = $this->urlPath;
        $consultation->title              = $this->title;
        $consultation->titleShort         = $this->titleShort;
        $consultation->wordingBase        = $this->template->wordingBase;
        $consultation->adminEmail         = $this->template->adminEmail;
        $consultation->dateCreation       = date('Y-m-d H:i:s');
        $consultation->settings           = $this->template->settings;
        if (!$consultation->save()) {
            throw new FormError(implode(', ', $consultation->getErrors()));
        }

        foreach ($this->template->motionTypes as $motionType) {
            $newType = new ConsultationMotionType();
            $newType->setAttributes($motionType->getAttributes(), false);
            $newType->consultationId = $consultation->id;
            $newType->id             = null;
            if (!$newType->save()) {
                throw new FormError($newType->getErrors());
            }

            foreach ($motionType->motionSections as $section) {
                $newSection = new ConsultationSettingsMotionSection();
                $newSection->setAttributes($section->getAttributes(), false);
                $newSection->motionTypeId = $newType->id;
                $newSection->id           = null;
                if (!$newSection->save()) {
                    throw new FormError($newType->getErrors());
                }
            }
        }

        foreach ($this->template->texts as $text) {
            $newText = new ConsultationText();
            $newText->setAttributes($text->getAttributes(), false);
            $newText->consultationId = $consultation->id;
            $newText->id             = null;
            if (!$newText->save()) {
                throw new FormError(implode(', ', $newText->getErrors()));
            }
        }

        foreach ($this->template->tags as $tag) {
            $newTag = new ConsultationSettingsTag();
            $newTag->setAttributes($tag->getAttributes(), false);
            $newTag->consultationId = $consultation->id;
            $newTag->id             = null;
            if (!$newTag->save()) {
                throw new FormError(implode(', ', $newTag->getErrors()));
            }
        }

        foreach ($this->template->userPrivileges as $priv) {
            $newPriv = new ConsultationUserPrivilege();
            $newPriv->setAttributes($priv->getAttributes(), false);
            $newPriv->consultationId = $consultation->id;
            if (!$newPriv->save()) {
                throw new FormError(implode(', ', $newPriv->getErrors()));
            }
        }

        if ($this->setAsDefault) {
            $this->site->currentConsultationId = $consultation->id;
            $this->site->save();
        }
    }

    /**
     * @throws FormError
     */
    private function createConsultationFromWizard()
    {
        $this->siteCreateWizard->subdomain = $this->site->subdomain;
        $this->siteCreateWizard->contact   = $this->site->contact;
        $this->siteCreateWizard->title     = $this->site->title;

        $user = User::getCurrentUser();

        $con               = new Consultation();
        $con->siteId       = $this->site->id;
        $con->urlPath      = $this->urlPath;
        $con->title        = $this->title;
        $con->titleShort   = $this->titleShort;
        $con->dateCreation = date('Y-m-d H:i:s');
        $con->adminEmail   = $user->email;

        $this->siteCreateWizard->createWithoutSite($user, $this->site, $con, $this->setAsDefault);
    }

    /**
     * @throws FormError
     */
    public function createConsultation()
    {
        if ($this->title == '' || $this->titleShort == '' || $this->urlPath == '') {
            throw new FormError(\Yii::t('wizard', 'cons_err_fields_missing'));
        }
        foreach ($this->template->site->consultations as $cons) {
            if (mb_strtolower($cons->urlPath) == mb_strtolower($this->urlPath)) {
                throw new FormError(\Yii::t('wizard', 'cons_err_path_taken'));
            }
        }

        if ($this->settingsType == 'wizard') {
            $this->createConsultationFromWizard();
        }
        if ($this->settingsType == 'template') {
            $this->createConsultationFromTemplate();
        }
    }
}
