<?php

namespace app\models\forms;

use app\components\Tools;
use app\models\motionTypeTemplates\Application;
use app\models\motionTypeTemplates\Manifesto;
use app\models\motionTypeTemplates\PDFApplication;
use app\models\db\{Consultation, ConsultationAgendaItem, ConsultationMotionType, ConsultationText, Motion, MotionSupporter, Site, SpeechQueue, User};
use app\models\exceptions\FormError;
use app\models\policies\IPolicy;
use app\models\settings\{AntragsgruenApp, InitiatorForm};
use app\models\supportTypes\SupportBase;
use yii\base\Model;
use yii\helpers\Html;

class SiteCreateForm extends Model
{
    /** @var string */
    public $contact;
    public $title;
    public $subdomain;
    public $organization;

    // Sync with SiteCreateWizard.ts
    const FUNCTIONALITY_MOTIONS   = 1;
    const FUNCTIONALITY_MANIFESTO = 2;
    const FUNCTIONALITY_APPLICATIONS = 3;
    const FUNCTIONALITY_AGENDA = 4;
    const FUNCTIONALITY_SPEECH_LISTS = 5;

    public $functionality = [1];
    public $language;

    /** @var bool */
    public $singleMotion    = false;
    public $hasAmendments   = true;
    public $amendSinglePara = false;
    public $amendMerging    = false;
    public $motionScreening = true;
    public $amendScreening  = true;
    public $speechQuotas    = false;

    /** @var int */
    public $motionsInitiatedBy    = 2;
    public $amendmentsInitiatedBy = 2;
    const MOTION_INITIATED_ADMINS    = 1;
    const MOTION_INITIATED_LOGGED_IN = 2;
    const MOTION_INITIATED_ALL       = 3;

    /** @var int */
    public $applicationType = 1;

    /** @var null|\DateTime */
    public $motionDeadline = null;
    /** @var null|\DateTime */
    public $amendmentDeadline = null;

    public $needsSupporters = false;
    public $minSupporters   = 3;

    /** @var bool */
    public $hasComments = false;
    public $openNow = false;

    /** @var Consultation|null */
    public $consultation = null;
    /** @var Site|null */
    public $site;
    /** @var Motion|null */
    public $motion;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->language = \Yii::$app->language;
    }

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
     * @throws \app\models\exceptions\Internal
     */
    public function setAttributes($values, $safeOnly = true)
    {
        parent::setAttributes($values, $safeOnly);

        $this->functionality = array_map('intval', $values['functionality'] ?? [static::FUNCTIONALITY_MOTIONS]);
        $this->singleMotion          = ($values['singleMotion'] == 1);
        $this->hasAmendments         = ($values['hasAmendments'] == 1);
        $this->amendSinglePara       = ($values['amendSinglePara'] == 1);
        $this->motionScreening       = ($values['motionScreening'] == 1);
        $this->amendScreening        = ($values['amendScreening'] == 1);
        $this->amendMerging          = ($values['amendMerging'] == 1);
        $this->applicationType       = intval($values['applicationType']);
        $this->motionsInitiatedBy    = intval($values['motionsInitiatedBy']);
        $this->amendmentsInitiatedBy = intval($values['amendInitiatedBy']);
        if ($values['motionsDeadlineExists']) {
            $deadline = Tools::dateBootstraptime2sql($values['motionsDeadline']);
            if ($deadline) {
                /** @noinspection PhpUnhandledExceptionInspection */
                $this->motionDeadline = new \DateTime($deadline);
            }
        }
        if ($values['amendDeadlineExists']) {
            $deadline = Tools::dateBootstraptime2sql($values['amendDeadline']);
            if ($deadline) {
                /** @noinspection PhpUnhandledExceptionInspection */
                $this->amendmentDeadline = new \DateTime($deadline);
            }
        }
        $this->needsSupporters = ($values['needsSupporters'] == 1);
        $this->minSupporters   = intval($values['minSupporters']);
        $this->hasComments     = ($values['hasComments'] == 1);
        $this->speechQuotas    = ($values['speechQuotas'] == 1);
        $this->openNow         = ($values['openNow'] == 1);
    }

    public function createSite(User $user): Site
    {
        $site               = new Site();
        $site->title        = $this->title;
        $site->titleShort   = mb_substr($this->title, 0, Site::TITLE_SHORT_MAX_LEN);
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
     * @throws FormError
     * @throws \Exception
     */
    public function createConsultationWithSubtypes(User $currentUser, Site $site, Consultation  $con, bool $setDefault): Consultation
    {
        $this->createConsultation($con);
        if ($setDefault) {
            $site->link('currentConsultation', $con);
        }

        $this->createMotionTypes($con, $currentUser);

        if (in_array(static::FUNCTIONALITY_AGENDA, $this->functionality)) {
            $this->createAgenda($con);
        }
        if (in_array(static::FUNCTIONALITY_SPEECH_LISTS, $this->functionality)) {
            $this->createSpeechList($con);
        }

        $this->createPageData($site, $con);

        return $con;
    }

    /**
     * @throws FormError
     * @throws \Exception
     */
    public function createConsultation(Consultation $con): void
    {
        $con->amendmentNumbering = 0;
        $con->dateCreation       = date('Y-m-d H:i:s');
        if ($this->language === 'de') {
            if (in_array(static::FUNCTIONALITY_MANIFESTO, $this->functionality) && !in_array(static::FUNCTIONALITY_MOTIONS, $this->functionality)) {
                $con->wordingBase = 'de-programm';
            } else {
                $con->wordingBase = 'de-parteitag';
            }
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
        if (in_array(static::FUNCTIONALITY_AGENDA, $this->functionality)) {
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
     * @throws FormError
     * @throws \Exception
     */
    public function create(User $currentUser): Consultation
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
        $con->titleShort = mb_substr($this->title, 0, Consultation::TITLE_SHORT_MAX_LEN);
        $con->urlPath    = $this->subdomain;
        $con->adminEmail = $currentUser->email;

        $this->createConsultationWithSubtypes($currentUser, $site, $con, true);

        return $con;
    }

    /**
     * @throws FormError
     */
    public function createMotionTypes(Consultation $con, User $user): void
    {
        $type = null;

        if (in_array(static::FUNCTIONALITY_APPLICATIONS, $this->functionality)) {
            if ($this->applicationType == 2) {
                $type = PDFApplication::doCreateApplicationType($con);
                PDFApplication::doCreateApplicationSections($type);
            } else {
                $type = Application::doCreateApplicationType($con);
                Application::doCreateApplicationSections($type);
            }
            $this->doFixApplicationMotionType($type);
        }
        if (in_array(static::FUNCTIONALITY_MANIFESTO, $this->functionality)) {
            $type = $this->doCreateManifestoType($con);
            Manifesto::doCreateManifestoSections($type);
        }
        if (in_array(static::FUNCTIONALITY_MOTIONS, $this->functionality)) {
            $type = $this->doCreateMotionType($con);
            \app\models\motionTypeTemplates\Motion::doCreateMotionSections($type);
        }

        if ($this->singleMotion && $type) {
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

            $supporter               = new MotionSupporter();
            $supporter->motionId     = $motion->id;
            $supporter->userId       = $user->id;
            $supporter->role         = MotionSupporter::ROLE_INITIATOR;
            $supporter->position     = 0;
            $supporter->dateCreation = date('Y-m-d H:i:s');
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
     * @throws FormError
     */
    private function doCreateManifestoType(Consultation $consultation): ConsultationMotionType
    {
        $type = Manifesto::doCreateManifestoType($consultation);

        $type->sidebarCreateButton = 1;
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
            $type->initiatorsCanMergeAmendments = ConsultationMotionType::INITIATORS_MERGE_NO_COLLISION;
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
        $type->policySupportMotions        = IPolicy::POLICY_NOBODY;
        $type->policySupportAmendments     = IPolicy::POLICY_NOBODY;

        $initiatorSettings              = new InitiatorForm(null);
        $initiatorSettings->type        = SupportBase::ONLY_INITIATOR;
        $initiatorSettings->contactName = InitiatorForm::CONTACT_NONE;
        if ($this->singleMotion) {
            $initiatorSettings->contactPhone = InitiatorForm::CONTACT_NONE;
            $initiatorSettings->contactEmail = InitiatorForm::CONTACT_NONE;
        } else {
            $initiatorSettings->contactPhone = InitiatorForm::CONTACT_OPTIONAL;
            $initiatorSettings->contactEmail = InitiatorForm::CONTACT_REQUIRED;
        }
        $type->supportTypeMotions = json_encode($initiatorSettings, JSON_PRETTY_PRINT);

        $deadlineMotions    = ($this->motionDeadline ? $this->motionDeadline->format('Y-m-d H:i:s') : null);
        $deadlineAmendments = ($this->amendmentDeadline ? $this->amendmentDeadline->format('Y-m-d H:i:s') : null);
        $type->setSimpleDeadlines($deadlineMotions, $deadlineAmendments);

        if (!$type->save()) {
            throw new FormError($type->getErrors());
        }

        return $type;
    }

    /**
     * @throws FormError
     */
    private function doCreateMotionType(Consultation $consultation): ConsultationMotionType
    {
        $type = \app\models\motionTypeTemplates\Motion::doCreateMotionType($consultation);

        $type->sidebarCreateButton = 1;
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
            $type->initiatorsCanMergeAmendments = ConsultationMotionType::INITIATORS_MERGE_NO_COLLISION;
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
        $type->policySupportMotions        = IPolicy::POLICY_NOBODY;
        $type->policySupportAmendments     = IPolicy::POLICY_NOBODY;
        $type->amendmentMultipleParagraphs = ($this->amendSinglePara ? 0 : 1);

        $initiatorSettings               = new InitiatorForm(null);
        $initiatorSettings->contactName  = InitiatorForm::CONTACT_NONE;
        $initiatorSettings->contactPhone = InitiatorForm::CONTACT_OPTIONAL;
        $initiatorSettings->contactEmail = InitiatorForm::CONTACT_REQUIRED;
        if ($this->needsSupporters) {
            $initiatorSettings->type          = SupportBase::GIVEN_BY_INITIATOR;
            $initiatorSettings->minSupporters = $this->minSupporters;
        } else {
            $initiatorSettings->type = SupportBase::ONLY_INITIATOR;
        }
        $type->supportTypeMotions    = json_encode($initiatorSettings, JSON_PRETTY_PRINT);

        $deadlineMotions    = ($this->motionDeadline ? $this->motionDeadline->format('Y-m-d H:i:s') : null);
        $deadlineAmendments = ($this->amendmentDeadline ? $this->amendmentDeadline->format('Y-m-d H:i:s') : null);
        $type->setSimpleDeadlines($deadlineMotions, $deadlineAmendments);

        if (!$type->save()) {
            throw new FormError($type->getErrors());
        }

        return $type;
    }

    private function doFixApplicationMotionType(ConsultationMotionType $motionType): void
    {
        $motionType->sidebarCreateButton = 1;
        $motionType->save();
    }

    private function createAgenda(Consultation $consultation): void
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

    private function createSpeechList(Consultation $consultation): void
    {
        if ($this->speechQuotas) {
            $subqueues = [
                \Yii::t('speech', 'subqueue_female'),
                \Yii::t('speech', 'subqueue_male'),
            ];
        } else {
            $subqueues = [];
        }

        $settings = $consultation->getSettings();
        $settings->hasSpeechLists = true;
        $settings->speechListSubqueues = $subqueues;
        $consultation->setSettings($settings);
        $consultation->save();

        $unassignedQueue = SpeechQueue::createWithSubqueues($consultation, true);
        $unassignedQueue->save();
    }

    /**
     * @throws FormError
     */
    private function createPageData(Site $site, Consultation $consultation): void
    {
        $contactHtml               = nl2br(Html::encode($site->contact));
        $legalText                 = new ConsultationText();
        $legalText->consultationId = null;
        $legalText->siteId         = $site->id;
        $legalText->category       = 'pagedata';
        $legalText->textId         = 'legal';
        $legalText->text           = str_replace('%CONTACT%', $contactHtml, \Yii::t('base', 'legal_template'));
        if (!$legalText->save()) {
            throw new FormError($legalText->getErrors());
        }

        $params = AntragsgruenApp::getInstance();
        if ($params->mode === 'sandbox') {
            $siteurl                   = str_replace('<subdomain:[\w_-]+>', $this->subdomain, $params->domainSubdomain);
            $welcomeHtml               = str_replace(
                ['%ADMIN_USERNAME%', '%ADMIN_PASSWORD%', '%SITE_URL%'],
                [$this->subdomain . '@example.org', 'admin', $siteurl],
                \Yii::t('wizard', 'sandbox_dummy_welcome')
            );
            $legalText                 = new ConsultationText();
            $legalText->siteId         = $site->id;
            $legalText->consultationId = $consultation->id;
            $legalText->category       = 'pagedata';
            $legalText->textId         = 'welcome';
            $legalText->text           = $welcomeHtml;
            if (!$legalText->save()) {
                throw new FormError($legalText->getErrors());
            }
        }
    }

    public function setSandboxParams(): void
    {
        $this->contact      = \Yii::t('wizard', 'sandbox_dummy_contact');
        $this->organization = \Yii::t('wizard', 'sandbox_dummy_orga');
        $this->title        = \Yii::t('wizard', 'sandbox_dummy_title');
        $this->subdomain    = substr(md5(uniqid()), 0, 8);
        $this->openNow      = true;
    }

    public function createSandboxUser(): User
    {
        if (\Yii::$app->user) {
            \Yii::$app->user->logout();
        }

        $email                 = $this->subdomain . '@example.org';
        $user                  = new User();
        $user->auth            = 'email:' . $email;
        $user->email           = $email;
        $user->name            = 'Admin';
        $user->status          = User::STATUS_CONFIRMED;
        $user->emailConfirmed  = 1;
        $user->dateCreation    = date('Y-m-d H:i:s');
        $user->pwdEnc          = password_hash('admin', PASSWORD_DEFAULT);
        $user->organizationIds = '';
        $user->save();
        if (!$user) {
            var_dump($user->getErrors());
            die();
        }

        \Yii::$app->user->login($user, AntragsgruenApp::getInstance()->autoLoginDuration);

        return $user;
    }
}
