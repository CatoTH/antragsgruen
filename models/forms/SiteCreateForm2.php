<?php

namespace app\models\forms;

use app\models\db\Consultation;
use app\models\db\Site;
use app\models\db\User;
use app\models\sitePresets\SitePresets;
use yii\base\Model;

class SiteCreateForm2 extends Model
{

    /** @var string */
    public $contact;
    public $title;
    public $subdomain;
    public $organization;

    /** @var int */
    public $isWillingToPay = null;

    const WORDING_MOTIONS   = 1;
    const WORDING_MANIFESTO = 2;
    public $wording = 1;

    /** @var bool */
    public $singleMotion  = true;
    public $hasAmendments = true;
    public $useScreening  = true;

    /** @var int */
    public $motionsInitiatedBy    = 2;
    public $amendmentsInitiatedBy = 2;
    const MOTION_INITIATED_ADMINS    = 1;
    const MOTION_INITIATED_LOGGED_IN = 2;
    const MOTION_INITIATED_ALL       = 3;

    /** @var null|\DateTime */
    public $motionDeadline    = null;
    public $amendmentDeadline = null;

    public $needsSupporters     = false;
    public $minSupporters       = 3;
    public $supportersWithOrgas = false;

    /** @var bool */
    public $hasComments     = false;
    public $hasAgenda       = false;
    public $hasApplications = false;

    public $openNow    = false;
    public $forceLogin = false;


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
