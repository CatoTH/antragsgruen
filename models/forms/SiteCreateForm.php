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
    public $hasAmendments = true;
    public $hasComments   = true;
    public $openNow       = true;

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
                'message' => 'Diese Subdomain wird bereits verwendet.'
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

        $site         = Site::createFromForm($this, $preset);
        $consultation = Consultation::createFromForm($this, $site, $currentUser, $preset);
        $site->link('currentConsultation', $consultation);
        $site->link('admins', $currentUser);

        $preset->createMotionTypes($consultation);
        $preset->createMotionSections($consultation);

        return $site;
    }
}
