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
    public $hasAmendmends = true;
    public $hasComments   = true;
    public $openNow       = true;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [
                ['contact', 'title', 'subdomain', 'isWillingToPay', 'preset', 'hasAmendments', 'hasComments'],
                'required'
            ],
            [['isWillingToPay', 'preset'], 'number'],
            [['hasAmendments', 'hasComments', 'openNow'], 'boolean'],
            ['subdomain', 'unique', 'targetClass' => Site::class],
            [['contact', 'title', 'preset'], 'safe'],
        ];
    }
}
