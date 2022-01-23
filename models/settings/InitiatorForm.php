<?php

namespace app\models\settings;

class InitiatorForm implements \JsonSerializable
{
    use JsonConfigTrait;

    const CONTACT_NONE = 0;
    const CONTACT_OPTIONAL = 1;
    const CONTACT_REQUIRED = 2;

    /** @var int */
    public $type = 0;

    /** @var bool */
    public $initiatorCanBePerson = true;
    /** @var bool */
    public $initiatorCanBeOrganization = true;

    /** @var int */
    public $minSupporters = 1;
    /** @var null|int */
    public $minSupportersFemale = null;
    /** @var null|int */
    public $maxPdfSupporters = null;
    /** @var bool */
    public $hasOrganizations = true;
    /** @var bool */
    public $allowMoreSupporters = true;
    /** @var int */
    public $hasResolutionDate = 2;
    /** @var bool */
    public $allowSupportingAfterPublication = false;
    /** @var bool */
    public $offerNonPublicSupports = false;

    // Used for CollectBeforePublish
    /** @var bool */
    public $skipForOrganizations = true;

    /** @var int */
    public $contactName = 0;
    /** @var int */
    public $contactPhone = 1;
    /** @var int */
    public $contactEmail = 2;
    /** @var int */
    public $contactGender = 0;

    /**
     * @throws \app\models\exceptions\FormError
     */
    public function saveFormTyped(array $formdata, array $affectedFields): void
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
