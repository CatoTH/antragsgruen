<?php

namespace app\models\settings;

class InitiatorForm implements \JsonSerializable
{
    use JsonConfigTrait;

    const CONTACT_NONE     = 0;
    const CONTACT_OPTIONAL = 1;
    const CONTACT_REQUIRED = 2;

    public $type = 0;

    public $initiatorCanBePerson       = true;
    public $initiatorCanBeOrganization = true;

    public $minSupporters       = 1;
    public $minSupportersFemale = null;
    public $maxPdfSupporters    = null;
    public $hasOrganizations    = true;
    public $allowMoreSupporters = true;
    public $hasResolutionDate   = 2;

    // Used for CollectBeforePublish
    public $skipForOrganizations = true;

    public $contactName   = 0;
    public $contactPhone  = 1;
    public $contactEmail  = 2;
    public $contactGender = 0;

    /**
     * @param array $formdata
     * @param array $affectedFields
     *
     * @throws \app\models\exceptions\FormError
     */
    public function saveFormTyped($formdata, $affectedFields)
    {
        if (isset($formdata['maxPdfSupporters']) && is_numeric($formdata['maxPdfSupporters']) && $formdata['maxPdfSupporters'] >= 0) {
            $formdata['maxPdfSupporters'] = intval($formdata['maxPdfSupporters']);
        } else {
            $formdata['maxPdfSupporters'] = null;
        }
        if (isset($formdata['minSupportersFemale']) && is_numeric($formdata['minSupportersFemale']) && $formdata['minSupportersFemale'] >= 0) {
            $formdata['minSupportersFemale'] = intval($formdata['minSupportersFemale']);
        } else {
            $formdata['minSupportersFemale'] = null;
        }
        $this->saveForm($formdata, $affectedFields);
    }
}
