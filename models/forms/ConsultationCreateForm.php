<?php

declare(strict_types=1);

namespace app\models\forms;

use app\models\db\{Consultation, ConsultationMotionType, ConsultationSettingsMotionSection, ConsultationSettingsTag, ConsultationText, ConsultationUserGroup, Site, User};
use app\models\exceptions\FormError;

class ConsultationCreateForm
{
    public const SETTINGS_TYPE_TEMPLATE = 'template';
    public const SETTINGS_TYPE_WIZARD = 'wizard';

    public const SUBSELECTION_TAGS = 'tags';
    public const SUBSELECTION_MOTION_TYPES = 'motiontypes';
    public const SUBSELECTION_TEXTS = 'texts';
    public const SUBSELECTION_USERS = 'users';

    public string $settingsType;
    public string $urlPath = '';
    public string $title = '';
    public string $titleShort = '';
    /** @var string[] */
    public array $templateSubselection = [];

    public ?Consultation $template = null;
    public bool $setAsDefault = true;

    public SiteCreateForm $siteCreateWizard;

    public function __construct(private Site $site)
    {
        $this->siteCreateWizard = new SiteCreateForm();
    }

    public function setAttributes(array $values): void
    {
        $this->urlPath = $values['urlPath'];
        $this->title = $values['title'];
        $this->titleShort = $values['titleShort'];
        $this->settingsType = $values['settingsType'];
        $this->templateSubselection = $values['templateSubselect'] ?? [];
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

        if (in_array(self::SUBSELECTION_MOTION_TYPES, $this->templateSubselection)) {
            $this->createConsultationFromTemplate_motionTypes($consultation);
        }

        if (in_array(self::SUBSELECTION_TEXTS, $this->templateSubselection)) {
            $this->createConsultationFromTemplate_texts($consultation);
        }

        if (in_array(self::SUBSELECTION_TAGS, $this->templateSubselection)) {
            $this->createConsultationFromTemplate_tags($consultation);
        }

        if (in_array(self::SUBSELECTION_USERS, $this->templateSubselection)) {
            $this->createConsultationFromTemplate_users($consultation);
        } else {
            $consultation->createDefaultUserGroups();
        }

        if ($this->setAsDefault) {
            $this->site->currentConsultationId = $consultation->id;
            $this->site->save();
        }
    }

    private function createConsultationFromTemplate_motionTypes(Consultation $newConsultation): void
    {
        foreach ($this->template->motionTypes as $motionType) {
            $newType = new ConsultationMotionType();
            $newType->setAttributes($motionType->getAttributes(), false);
            $newType->consultationId = $newConsultation->id;
            $newType->id             = null;
            if (!$newType->save()) {
                throw new FormError($newType->getErrors());
            }

            foreach ($motionType->motionSections as $section) {
                $newSection = new ConsultationSettingsMotionSection();
                $newSection->setAttributes($section->getAttributes(), false);
                $newSection->motionTypeId = (int)$newType->id;
                $newSection->id           = null;
                if (!$newSection->save()) {
                    throw new FormError($newType->getErrors());
                }
            }
        }
    }

    private function createConsultationFromTemplate_texts(Consultation $newConsultation): void
    {
        foreach ($this->template->texts as $text) {
            $newText = new ConsultationText();
            $newText->setAttributes($text->getAttributes(), false);
            $newText->consultationId = $newConsultation->id;
            $newText->id             = null;
            if (!$newText->save()) {
                throw new FormError(implode(', ', $newText->getErrors()));
            }
        }
    }

    private function createConsultationFromTemplate_tags(Consultation $newConsultation): void
    {
        $newTagsByOldId = [];
        foreach ($this->template->tags as $tag) {
            $newTag = new ConsultationSettingsTag();
            $newTag->setAttributes($tag->getAttributes(), false);
            $newTag->id = null;
            $newTag->consultationId = $newConsultation->id;
            $newTag->parentTagId = null;
            if (!$newTag->save()) {
                throw new FormError(implode(', ', $newTag->getErrors()));
            }

            $newTagsByOldId[$tag->id] = $newTag;
        }
        foreach ($this->template->tags as $tag) {
            if ($tag->parentTagId === null) {
                continue;
            }
            $newTag = $newTagsByOldId[$tag->id];
            $newTag->parentTagId = $newTagsByOldId[$tag->parentTagId]->id;
            $newTag->save();
        }
    }

    private function createConsultationFromTemplate_users(Consultation $newConsultation): void
    {
        foreach ($this->template->userGroups as $userGroup) {
            $newGroup = new ConsultationUserGroup();
            $newGroup->setAttributes($userGroup->getAttributes(), false);
            $newGroup->id = null;
            $newGroup->consultationId = $newConsultation->id;
            if (!$newGroup->save()) {
                throw new FormError(implode(', ', $newGroup->getErrors()));
            }

            foreach ($userGroup->users as $user) {
                $newGroup->addUser($user);
            }
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
        $con->titleShort   = mb_substr($this->titleShort, 0, Consultation::TITLE_SHORT_MAX_LEN);
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

        if ($this->settingsType === self::SETTINGS_TYPE_WIZARD) {
            $this->createConsultationFromWizard();
        }
        if ($this->settingsType === self::SETTINGS_TYPE_TEMPLATE) {
            $this->createConsultationFromTemplate();
        }
    }
}
