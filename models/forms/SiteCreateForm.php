<?php

namespace app\models\forms;

class SiteCreateForm extends \yii\base\Model
{

    /** @var string */
    public $contact;
    public $title;
    public $subdomain;

    /** @var int */
    public $is_willing_to_pay = 0;
    public $preset            = 0;

    /** @var bool */
    public $has_amendmends = true;
    public $has_comments   = true;
    public $open_now       = true;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['contact, title, subdomain, is_willing_to_pay, preset, has_amendments, has_comments', 'required'],
            ['is_willing_to_pay, preset', 'numerical'],
            ['has_amendments, has_comments, open_now', 'boolean'],
            ['subdomain', 'unique', 'className' => 'Site'],
            ['contact, title, preset', 'safe'],
        ];
    }
}
