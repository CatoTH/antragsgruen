<?php

namespace app\models\forms;

use app\models\db\Site;

class SiteCreateForm extends \yii\base\Model
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
}
