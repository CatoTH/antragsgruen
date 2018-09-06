<?php

namespace app\models\settings;

class InitiatorForm implements \JsonSerializable
{
    use JsonConfigTrait;

    const CONTACT_NONE     = 0;
    const CONTACT_OPTIONAL = 1;
    const CONTACT_REQUIRED = 2;

    public $minSupporters       = 1;
    public $hasOrganizations    = true;
    public $allowMoreSupporters = true;
    public $hasResolutionDate   = 2;

    // Used for CollectBeforePublish
    public $skipForOrganizations = true;

    public $contactName   = 0;
    public $contactPhone  = 1;
    public $contactEmail  = 2;
    public $contactGender = 0;
}
