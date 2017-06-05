<?php

namespace app\models\forms;

use app\components\Tools;
use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\ConsultationText;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use app\models\db\Site;
use app\models\db\User;
use app\models\exceptions\FormError;
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;
use app\models\settings\AntragsgruenApp;
use app\models\supportTypes\ISupportType;
use yii\base\Model;
use yii\helpers\Html;

class SiteCreateForm extends Model
{

    /** @var string */
    public $contact;
    public $title;
    public $subdomain;
    public $organization;

    const WORDING_MOTIONS   = 1;
    const WORDING_MANIFESTO = 2;
    public $wording = 1;

    /** @var bool */
    public $singleMotion    = false;
    public $hasAmendments   = true;
    public $amendSinglePara = false;
    public $amendMerging    = false;
    public $motionScreening = true;
    public $amendScreening  = true;

    /** @var int */
    public $motionsInitiatedBy    = 2;
    public $amendmentsInitiatedBy = 2;
    const MOTION_INITIATED_ADMINS    = 1;
    const MOTION_INITIATED_LOGGED_IN = 2;
    const MOTION_INITIATED_ALL       = 3;

    /** @var null|\DateTime */
    public $motionDeadline = null;
    /** @var null|\DateTime */
    public $amendmentDeadline = null;

    public $needsSupporters = false;
    public $minSupporters   = 3;

    /** @var bool */
    public $hasComments = false;
    public $hasAgenda   = false;

    public $openNow = false;

    /** @var Consultation|null */
    public $consultation = null;
    /** @var Site|null */
    public $site;
    /** @var Motion|null */
    public $motion;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['title', 'contact', 'subdomain'], 'required'],
            [
                'subdomain',
                'unique',
                'targetClass' => Site::class,
            ],
            [['contact', 'title', 'subdomain', 'organization'], 'safe'],
        ];
    }

    /**
     * @param array $values
     * @param bool $safeOnly
     */
    public function setAttributes($values, $safeOnly = true)
    {
        parent::setAttributes($values, $safeOnly);

        $this->wording               = IntVal($values['wording']);
        $this->singleMotion          = ($values['singleMotion'] == 1);
        $this->hasAmendments         = ($values['hasAmendments'] == 1);
        $this->amendSinglePara       = ($values['amendSinglePara'] == 1);
        $this->motionScreening       = ($values['motionScreening'] == 1);
        $this->amendScreening        = ($values['amendScreening'] == 1);
        $this->amendMerging          = ($values['amendMerging'] == 1);
        $this->motionsInitiatedBy    = IntVal($values['motionsInitiatedBy']);
        $this->amendmentsInitiatedBy = IntVal($values['amendInitiatedBy']);
        if ($values['motionsDeadlineExists']) {
            $deadline = Tools::dateBootstraptime2sql($values['motionsDeadline']);
            if ($deadline) {
                $this->motionDeadline = new \DateTime($deadline);
            }
        }
        if ($values['amendDeadlineExists']) {
            $deadline = Tools::dateBootstraptime2sql($values['amendDeadline']);
            if ($deadline) {
                $this->amendmentDeadline = new \DateTime($deadline);
            }
        }
        $this->needsSupporters = ($values['needsSupporters'] == 1);
        $this->minSupporters   = IntVal($values['minSupporters']);
        $this->hasComments     = ($values['hasComments'] == 1);
        $this->hasAgenda       = ($values['hasAgenda'] == 1);
        $this->openNow         = ($values['openNow'] == 1);
    }

    /**
     * @param User $user
     * @return Site
     * @throws FormError
     */
    public function createSite(User $user)
    {
        $site               = new Site();
        $site->title        = $this->title;
        $site->titleShort   = $this->title;
        $site->organization = $this->organization;
        $site->contact      = $this->contact;
        $site->subdomain    = $this->subdomain;
        $site->public       = 1;
        $site->status       = ($this->openNow ? Site::STATUS_ACTIVE : Site::STATUS_INACTIVE);
        $site->dateCreation = date('Y-m-d H:i:s');
        if (!$site->save()) {
            throw new FormError($site->getErrors());
        }
        $this->site = $site;

        $site->link('admins', $user);

        return $site;
    }

    /**
     * @param Consultation $con
     * @throws FormError
     * @throws \Exception
     */
    public function createConsultation(Consultation $con)
    {
        $con->amendmentNumbering = 0;
        $con->dateCreation       = date('Y-m-d H:i:s');
        if (\Yii::$app->language == 'de') {
            $con->wordingBase = ($this->wording == static::WORDING_MANIFESTO ? 'de-programm' : 'de-parteitag');
        } else {
            $con->wordingBase = \Yii::$app->language;
        }

        $settings                  = $con->getSettings();
        $settings->maintenanceMode = !$this->openNow;
        if ($this->motionsInitiatedBy == static::MOTION_INITIATED_ADMINS) {
            $settings->screeningMotions = false;
        } else {
            $settings->screeningMotions = $this->motionScreening;
        }
        if ($this->amendmentsInitiatedBy == static::MOTION_INITIATED_ADMINS) {
            $settings->screeningAmendments = false;
        } else {
            $settings->screeningAmendments = $this->amendScreening;
        }
        if ($this->hasAgenda) {
            $settings->startLayoutType = \app\models\settings\Consultation::START_LAYOUT_AGENDA_LONG;
        } else {
            $settings->startLayoutType = \app\models\settings\Consultation::START_LAYOUT_STD;
        }
        $settings->screeningComments = false;
        $con->setSettings($settings);
        if (!$con->save()) {
            throw new FormError($con->getErrors());
        }
        $this->consultation = $con;
    }

    /**
     * @param Consultation $con
     * @param User $user
     * @throws FormError
     */
    public function createMotionTypes(Consultation $con, User $user)
    {
        if ($this->wording == static::WORDING_MANIFESTO) {
            $type = $this->doCreateManifestoType($con);
            $this->doCreateManifestoSections($type);
        } else {
            $type = $this->doCreateMotionType($con);
            $this->doCreateMotionSections($type);
        }

        if ($this->singleMotion) {
            $motion                 = new Motion();
            $motion->title          = '';
            $motion->titlePrefix    = '';
            $motion->cache          = '';
            $motion->consultationId = $con->id;
            $motion->motionTypeId   = $type->id;
            $motion->dateCreation   = date('Y-m-d H:i:s');
            $motion->status         = Motion::STATUS_DRAFT;
            if (!$motion->save()) {
                throw new FormError($motion->getErrors());
            }

            $supporter           = new MotionSupporter();
            $supporter->motionId = $motion->id;
            $supporter->userId   = $user->id;
            $supporter->role     = MotionSupporter::ROLE_INITIATOR;
            $supporter->position = 0;
            if (!$supporter->save()) {
                throw new FormError($motion->getErrors());
            }

            $this->motion = $motion;

            $conSett                   = $this->consultation->getSettings();
            $conSett->forceMotion      = $motion->id;
            $conSett->screeningMotions = false;
            $this->consultation->setSettings($conSett);
            $this->consultation->save();
        }
    }

    /**
     * @param User $currentUser
     * @param Site $site
     * @param Consultation $con
     * @param bool $setDefault
     * @return Consultation
     * @throws FormError
     */
    public function createWithoutSite(User $currentUser, $site, $con, $setDefault = true)
    {
        $this->createConsultation($con);
        if ($setDefault) {
            $site->link('currentConsultation', $con);
        }

        $this->createMotionTypes($con, $currentUser);

        if ($this->hasAgenda) {
            $this->createAgenda($con);
        }

        $this->createPageData($site, $con);

        return $con;
    }

    /**
     * @param User $currentUser
     * @return Consultation
     * @throws FormError
     */
    public function create(User $currentUser)
    {
        if (!Site::isSubdomainAvailable($this->subdomain)) {
            throw new FormError(\Yii::t('manager', 'site_err_subdomain'));
        }
        if (!$this->validate()) {
            throw new FormError($this->getErrors());
        }
        $site = $this->createSite($currentUser);

        $con             = new Consultation();
        $con->siteId     = $site->id;
        $con->title      = $this->title;
        $con->titleShort = $this->title;
        $con->urlPath    = $this->subdomain;
        $con->adminEmail = $currentUser->email;
        $this->createConsultation($con);

        $site->link('currentConsultation', $con);

        $this->createMotionTypes($con, $currentUser);

        if ($this->hasAgenda) {
            $this->createAgenda($con);
        }

        $this->createPageData($site, $con);

        return $con;
    }


    /**
     * @param Consultation $consultation
     * @return ConsultationMotionType
     * @throws FormError
     */
    private function doCreateManifestoType(Consultation $consultation)
    {
        $type                 = new ConsultationMotionType();
        $type->consultationId = $consultation->id;
        $type->titleSingular  = \Yii::t('structure', 'preset_manifesto_singular');
        $type->titlePlural    = \Yii::t('structure', 'preset_manifesto_plural');
        $type->createTitle    = \Yii::t('structure', 'preset_manifesto_call');
        $type->position       = 0;
        if ($this->motionsInitiatedBy == static::MOTION_INITIATED_ADMINS) {
            $type->policyMotions = IPolicy::POLICY_ADMINS;
        } elseif ($this->motionsInitiatedBy == static::MOTION_INITIATED_LOGGED_IN) {
            $type->policyMotions = IPolicy::POLICY_LOGGED_IN;
        } else {
            $type->policyMotions = IPolicy::POLICY_ALL;
        }
        if (!$this->hasAmendments) {
            $type->policyAmendments = IPolicy::POLICY_NOBODY;
        } elseif ($this->amendmentsInitiatedBy == static::MOTION_INITIATED_ADMINS) {
            $type->policyAmendments = IPolicy::POLICY_ADMINS;
        } elseif ($this->amendmentsInitiatedBy == static::MOTION_INITIATED_LOGGED_IN) {
            $type->policyAmendments = IPolicy::POLICY_LOGGED_IN;
        } else {
            $type->policyAmendments = IPolicy::POLICY_ALL;
        }
        if ($this->amendMerging) {
            $type->initiatorsCanMergeAmendments = ConsultationMotionType::INITIATORS_MERGE_NO_COLLISSION;
        } else {
            $type->initiatorsCanMergeAmendments = ConsultationMotionType::INITIATORS_MERGE_NEVER;
        }
        if ($this->hasComments) {
            if (in_array($type->policyAmendments, [IPolicy::POLICY_ALL, IPolicy::POLICY_LOGGED_IN])) {
                $type->policyComments = $type->policyAmendments;
            } else {
                $type->policyComments = IPolicy::POLICY_ALL;
            }
        } else {
            $type->policyComments = IPolicy::POLICY_NOBODY;
        }
        $type->policySupportMotions    = IPolicy::POLICY_NOBODY;
        $type->policySupportAmendments = IPolicy::POLICY_NOBODY;
        $type->contactName             = ConsultationMotionType::CONTACT_NONE;
        if ($this->singleMotion) {
            $type->contactPhone = ConsultationMotionType::CONTACT_NONE;
            $type->contactEmail = ConsultationMotionType::CONTACT_NONE;
        } else {
            $type->contactPhone = ConsultationMotionType::CONTACT_OPTIONAL;
            $type->contactEmail = ConsultationMotionType::CONTACT_REQUIRED;
        }
        $type->supportType                 = ISupportType::ONLY_INITIATOR;
        $type->texTemplateId               = 1;
        $type->amendmentMultipleParagraphs = 1;
        $type->motionLikesDislikes         = 0;
        $type->amendmentLikesDislikes      = 0;
        $type->status                      = ConsultationMotionType::STATUS_VISIBLE;
        $type->layoutTwoCols               = 0;
        if ($this->motionDeadline) {
            $type->deadlineMotions = $this->motionDeadline->format('Y-m-d H:i:s');
        } else {
            $type->deadlineMotions = null;
        }
        if ($this->amendmentDeadline) {
            $type->deadlineAmendments = $this->amendmentDeadline->format('Y-m-d H:i:s');
        } else {
            $type->deadlineAmendments = null;
        }

        if (!$type->save()) {
            throw new FormError($type->getErrors());
        }

        return $type;
    }

    /**
     * @param ConsultationMotionType $motionType
     */
    private function doCreateManifestoSections(ConsultationMotionType $motionType)
    {
        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TITLE;
        $section->position      = 0;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_manifesto_title');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 1;
        $section->positionRight = 0;
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->position      = 1;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_manifesto_text');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 1;
        $section->lineNumbers   = 1;
        $section->hasComments   = 1;
        $section->hasAmendments = 1;
        $section->positionRight = 0;
        $section->save();
    }

    /**
     * @param Consultation $consultation
     * @return ConsultationMotionType
     * @throws FormError
     */
    private function doCreateMotionType(Consultation $consultation)
    {
        $type                 = new ConsultationMotionType();
        $type->consultationId = $consultation->id;
        $type->titleSingular  = \Yii::t('structure', 'preset_motion_singular');
        $type->titlePlural    = \Yii::t('structure', 'preset_motion_plural');
        $type->createTitle    = \Yii::t('structure', 'preset_motion_call');
        $type->position       = 0;
        if ($this->motionsInitiatedBy == static::MOTION_INITIATED_ADMINS) {
            $type->policyMotions = IPolicy::POLICY_ADMINS;
        } elseif ($this->motionsInitiatedBy == static::MOTION_INITIATED_LOGGED_IN) {
            $type->policyMotions = IPolicy::POLICY_LOGGED_IN;
        } else {
            $type->policyMotions = IPolicy::POLICY_ALL;
        }
        if (!$this->hasAmendments) {
            $type->policyAmendments = IPolicy::POLICY_NOBODY;
        } elseif ($this->amendmentsInitiatedBy == static::MOTION_INITIATED_ADMINS) {
            $type->policyAmendments = IPolicy::POLICY_ADMINS;
        } elseif ($this->amendmentsInitiatedBy == static::MOTION_INITIATED_LOGGED_IN) {
            $type->policyAmendments = IPolicy::POLICY_LOGGED_IN;
        } else {
            $type->policyAmendments = IPolicy::POLICY_ALL;
        }
        if ($this->amendMerging) {
            $type->initiatorsCanMergeAmendments = ConsultationMotionType::INITIATORS_MERGE_NO_COLLISSION;
        } else {
            $type->initiatorsCanMergeAmendments = ConsultationMotionType::INITIATORS_MERGE_NEVER;
        }
        if ($this->hasComments) {
            if (in_array($type->policyAmendments, [IPolicy::POLICY_ALL, IPolicy::POLICY_LOGGED_IN])) {
                $type->policyComments = $type->policyAmendments;
            } else {
                $type->policyComments = IPolicy::POLICY_ALL;
            }
        } else {
            $type->policyComments = IPolicy::POLICY_NOBODY;
        }
        $type->policySupportMotions    = IPolicy::POLICY_NOBODY;
        $type->policySupportAmendments = IPolicy::POLICY_NOBODY;
        $type->contactName             = ConsultationMotionType::CONTACT_NONE;
        $type->contactPhone            = ConsultationMotionType::CONTACT_OPTIONAL;
        $type->contactEmail            = ConsultationMotionType::CONTACT_REQUIRED;
        if ($this->needsSupporters) {
            $type->supportType         = ISupportType::GIVEN_BY_INITIATOR;
            $type->supportTypeSettings = json_encode([
                'minSupporters'               => $this->minSupporters,
                'supportersHaveOrganizations' => false,
            ]);
        } else {
            $type->supportType = ISupportType::ONLY_INITIATOR;
        }
        $type->texTemplateId               = 1;
        $type->amendmentMultipleParagraphs = ($this->amendSinglePara ? 0 : 1);
        $type->motionLikesDislikes         = 0;
        $type->amendmentLikesDislikes      = 0;
        $type->status                      = ConsultationMotionType::STATUS_VISIBLE;
        $type->layoutTwoCols               = 0;
        if ($this->motionDeadline) {
            $type->deadlineMotions = $this->motionDeadline->format('Y-m-d H:i:s');
        } else {
            $type->deadlineMotions = null;
        }
        if ($this->amendmentDeadline) {
            $type->deadlineAmendments = $this->amendmentDeadline->format('Y-m-d H:i:s');
        } else {
            $type->deadlineAmendments = null;
        }

        if (!$type->save()) {
            throw new FormError($type->getErrors());
        }

        return $type;
    }

    /**
     * @param ConsultationMotionType $motionType
     */
    private function doCreateMotionSections(ConsultationMotionType $motionType)
    {
        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TITLE;
        $section->position      = 0;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_motion_title');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 1;
        $section->positionRight = 0;
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->position      = 1;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_motion_text');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 1;
        $section->lineNumbers   = 1;
        $section->hasComments   = 1;
        $section->hasAmendments = 1;
        $section->positionRight = 0;
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->position      = 2;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_motion_reason');
        $section->required      = 0;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 0;
        $section->positionRight = 0;
        $section->save();
    }

    /**
     * @param Consultation $consultation
     */
    private function createAgenda(Consultation $consultation)
    {
        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = null;
        $item->position       = 0;
        $item->code           = '0.';
        $item->title          = \Yii::t('structure', 'preset_party_top');
        $item->save();

        $wahlItem                 = new ConsultationAgendaItem();
        $wahlItem->consultationId = $consultation->id;
        $wahlItem->parentItemId   = null;
        $wahlItem->position       = 1;
        $wahlItem->code           = '#';
        $wahlItem->title          = \Yii::t('structure', 'preset_party_elections');
        $wahlItem->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = $wahlItem->id;
        $item->position       = 0;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_1leader');
        $item->motionTypeId   = null;
        $item->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = $wahlItem->id;
        $item->position       = 1;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_2leader');
        $item->motionTypeId   = null;
        $item->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = $wahlItem->id;
        $item->position       = 2;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_treasure');
        $item->motionTypeId   = null;
        $item->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = null;
        $item->position       = 2;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_motions');
        $item->motionTypeId   = null;
        $item->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = null;
        $item->position       = 3;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_misc');
        $item->save();
    }

    /**
     * @var Site $site
     * @param Consultation $consultation
     * @throws FormError
     */
    private function createPageData(Site $site, Consultation $consultation)
    {
        $contactHtml               = nl2br(Html::encode($site->contact));
        $legalText                 = new ConsultationText();
        $legalText->consultationId = $consultation->id;
        $legalText->category       = 'pagedata';
        $legalText->textId         = 'legal';
        $legalText->text           = str_replace('%CONTACT%', $contactHtml, \Yii::t('base', 'legal_template'));
        if (!$legalText->save()) {
            throw new FormError($legalText->getErrors());
        }

        $params = AntragsgruenApp::getInstance();
        if ($params->mode == 'sandbox') {
            $siteurl                   = str_replace('<subdomain:[\w_-]+>', $this->subdomain, $params->domainSubdomain);
            $welcomeHtml               = str_replace(
                ['%ADMIN_USERNAME%', '%ADMIN_PASSWORD%', '%SITE_URL%'],
                [$this->subdomain . '@example.org', 'admin', $siteurl],
                \Yii::t('wizard', 'sandbox_dummy_welcome')
            );
            $legalText                 = new ConsultationText();
            $legalText->consultationId = $consultation->id;
            $legalText->category       = 'pagedata';
            $legalText->textId         = 'welcome';
            $legalText->text           = $welcomeHtml;
            if (!$legalText->save()) {
                throw new FormError($legalText->getErrors());
            }
        }
    }

    /**
     */
    public function setSandboxParams()
    {
        $this->contact      = \Yii::t('wizard', 'sandbox_dummy_contact');
        $this->organization = \Yii::t('wizard', 'sandbox_dummy_orga');
        $this->title        = \Yii::t('wizard', 'sandbox_dummy_title');
        $this->subdomain    = substr(md5(uniqid()), 0, 8);
        $this->openNow      = true;
    }

    /**
     * @return User
     */
    public function createSandboxUser()
    {
        if (\Yii::$app->user) {
            \Yii::$app->user->logout();
        }

        $email                = $this->subdomain . '@example.org';
        $user                 = new User();
        $user->auth           = 'email:' . $email;
        $user->email          = $email;
        $user->name           = 'Admin';
        $user->status         = User::STATUS_CONFIRMED;
        $user->emailConfirmed = true;
        $user->dateCreation   = date('Y-m-d H:i:s');
        $user->pwdEnc         = password_hash('admin', PASSWORD_DEFAULT);
        $user->save();
        if (!$user) {
            var_dump($user->getErrors());
            die();
        }

        \Yii::$app->user->login($user, AntragsgruenApp::getInstance()->autoLoginDuration);

        return $user;
    }
}
