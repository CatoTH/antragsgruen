<?php

namespace app\models\forms;

use app\components\Tools;
use app\models\db\Consultation;
use app\models\db\Site;
use app\models\db\User;
use app\models\exceptions\FormError;
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
    public function createSite(User $currentUser)
    {
        var_dump($this);
        if (!Site::isSubdomainAvailable($this->subdomain)) {
            throw new FormError(\Yii::t('manager', 'site_err_subdomain'));
        }
        if (!$this->validate()) {
            throw new FormError($this->getErrors());
        }

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
        
        $con                     = new Consultation();
        $con->siteId             = $site->id;
        $con->title              = $this->title;
        $con->titleShort         = $this->title;
        $con->urlPath            = $this->subdomain;
        $con->adminEmail         = $currentUser->email;
        $con->amendmentNumbering = 0;
        $con->dateCreation       = date('Y-m-d H:i:s');

        $settings                   = $con->getSettings();
        $settings->maintainanceMode = !$this->openNow;
        $con->setSettings($settings);
        if (!$con->save()) {
            $site->delete();
            throw new FormError($con->getErrors());
        }

        $site->link('currentConsultation', $con);
        $site->link('admins', $currentUser);

        die();

        return $site;
    }
}
