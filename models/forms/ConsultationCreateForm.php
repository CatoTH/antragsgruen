<?php

namespace app\models\forms;

use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\ConsultationSettingsTag;
use app\models\db\ConsultationText;
use app\models\db\ConsultationUserGroup;
use app\models\db\Site;
use app\models\db\User;
use app\models\exceptions\FormError;

class ConsultationCreateForm
{
    private Site $site;

    public string $settingsType;
    public string $urlPath = '';
    public string $title = '';
    public string $titleShort = '';

    public ?Consultation $template = null;
    public bool $setAsDefault = true;

    public SiteCreateForm $siteCreateWizard;

    public function __construct(Site $site)
    {
        $this->site             = $site;
        $this->siteCreateWizard = new SiteCreateForm();
    }

    public function setAttributes(array $values): void
    {
        $this->urlPath = $values['urlPath'];
        $this->title = $values['title'];
        $this->titleShort = $values['titleShort'];
        $this->settingsType = $values['settingsType'];
        $this->setAsDefault = isset($values['setStandard']);
    }

    /**
     * @throws FormError
     */
    private function createConsultationFromTemplate(): void
    {
        $consultation                     = new Consultation();
        $consultation->siteId             = $this->site->id;
        $consultation->amendmentNumbering = $this->template->amendmentNumbering;
        $consultation->urlPath            = $this->urlPath;
        $consultation->title              = $this->title;
        $consultation->titleShort         = mb_substr($this->titleShort, 0, Consultation::TITLE_SHORT_MAX_LEN);
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
            $newTag->id = null;
            $newTag->consultationId = $consultation->id;
            if (!$newTag->save()) {
                throw new FormError(implode(', ', $newTag->getErrors()));
            }
        }

        foreach ($this->template->userGroups as $userGroup) {
            $newGroup = new ConsultationUserGroup();
            $newGroup->setAttributes($userGroup->getAttributes(), false);
            $newGroup->id = null;
            $newGroup->consultationId = $consultation->id;
            if (!$newGroup->save()) {
                throw new FormError(implode(', ', $newGroup->getErrors()));
            }

            foreach ($userGroup->users as $user) {
                $newGroup->addUser($user);
            }
        }

        if ($this->setAsDefault) {
            $this->site->currentConsultationId = $consultation->id;
            $this->site->save();
        }
    }

    /**
     * @throws FormError
     * @throws \Exception
     */
    private function createConsultationFromWizard(): void
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

        $this->siteCreateWizard->createConsultationWithSubtypes($user, $this->site, $con, $this->setAsDefault);
    }

    /**
     * @throws FormError
     * @throws \Exception
     */
    public function createConsultation(): void
    {
        if (!$this->title || !$this->titleShort || !$this->urlPath ) {
            throw new FormError(\Yii::t('wizard', 'cons_err_fields_missing'));
        }
        foreach ($this->template->site->consultations as $cons) {
            if (mb_strtolower($cons->urlPath) === mb_strtolower($this->urlPath)) {
                throw new FormError(\Yii::t('wizard', 'cons_err_path_taken'));
            }
        }

        if ($this->settingsType === 'wizard') {
            $this->createConsultationFromWizard();
        }
        if ($this->settingsType === 'template') {
            $this->createConsultationFromTemplate();
        }
    }
}
