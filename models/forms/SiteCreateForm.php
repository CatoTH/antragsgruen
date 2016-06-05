<?php

namespace app\models\forms;

use app\components\Tools;
use app\models\db\Consultation;
use app\models\db\Site;
use app\models\db\User;
use yii\base\Model;

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
    public $motionScreening = true;
    public $amendScreening  = true;

    /** @var int */
    public $motionsInitiatedBy    = 2;
    public $amendmentsInitiatedBy = 2;
    const MOTION_INITIATED_ADMINS    = 1;
    const MOTION_INITIATED_LOGGED_IN = 2;
    const MOTION_INITIATED_ALL       = 3;

    /** @var null|\DateTime */
    public $motionDeadline    = null;
    public $amendmentDeadline = null;

    public $needsSupporters = false;
    public $minSupporters   = 3;

    /** @var bool */
    public $hasComments = false;
    public $hasAgenda   = false;

    public $openNow = false;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['title', 'contact', 'organization', 'subdomain'], 'required'],
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
     * @param User $currentUser
     * @return Site
     * @throws \app\models\exceptions\DB
     */
    public function createSiteFromForm(User $currentUser)
    {
        var_dump($this);
        die();
        $preset = SitePresets::getPreset($this->preset);

        $site         = Site::createFromForm(
            $preset,
            $this->subdomain,
            $this->title,
            $this->organization,
            $this->contact,
            $this->isWillingToPay,
            ($this->openNow ? Site::STATUS_ACTIVE : Site::STATUS_INACTIVE)
        );
        $consultation = Consultation::createFromForm(
            $site,
            $currentUser,
            $preset,
            $this->preset,
            $this->title,
            $this->subdomain,
            $this->openNow
        );
        $site->link('currentConsultation', $consultation);
        $site->link('admins', $currentUser);

        $preset->createMotionTypes($consultation);
        $preset->createMotionSections($consultation);
        $preset->createAgenda($consultation);

        return $site;
    }
}
