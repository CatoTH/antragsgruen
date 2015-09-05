<?php

namespace app\models\forms;

use app\models\db\Consultation;
use app\models\db\Site;
use app\models\db\User;
use app\models\sitePresets\SitePresets;
use yii\base\Model;

class SiteCreateForm extends Model
{

    /** @var string */
    public $contact;
    public $title;
    public $subdomain;

    /** @var int */
    public $isWillingToPay = null;
    public $preset         = 0;

    /** @var bool */
    public $hasAmendments = false;
    public $hasComments   = false;
    public $openNow       = false;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [
                ['title', 'subdomain', 'isWillingToPay', 'preset', 'hasAmendments', 'hasComments'],
                'required'
            ],
            [
                'contact', 'required', 'message' => 'Du musst eine Kontaktadresse angeben.'
            ],
            [['isWillingToPay', 'preset'], 'number'],
            [['hasAmendments', 'hasComments', 'openNow'], 'boolean'],
            [
                'subdomain',
                'unique',
                'targetClass' => Site::class,
                'message'     => 'Diese Subdomain wird bereits verwendet.'
            ],
            [['contact', 'title', 'preset'], 'safe'],
        ];
    }

    /**
     * @param User $currentUser
     * @return Site
     * @throws \app\models\exceptions\DB
     */
    public function createSiteFromForm(User $currentUser)
    {
        $preset = SitePresets::getPreset($this->preset);

        $site         = Site::createFromForm(
            $preset,
            $this->subdomain,
            $this->title,
            $this->isWillingToPay
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
