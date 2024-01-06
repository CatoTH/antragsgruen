<?php

namespace app\models\settings;

class InitiatorForm implements \JsonSerializable
{
    use JsonConfigTrait;

    public const CONTACT_NONE = 0;
    public const CONTACT_OPTIONAL = 1;
    public const CONTACT_REQUIRED = 2;

    public int $type = 0;

    public bool $initiatorCanBePerson = true;
    public bool $initiatorCanBeOrganization = true;

    public int $minSupporters = 1;
    public ?int $minSupportersFemale = null;
    public ?int $maxPdfSupporters = null;
    public bool $hasOrganizations = true;
    public bool $allowMoreSupporters = true;
    public int $hasResolutionDate = 2;
    public bool $allowSupportingAfterPublication = false;
    public bool $offerNonPublicSupports = false;

    // Used for CollectBeforePublish
    public bool $skipForOrganizations = true;

    public int $contactName = self::CONTACT_NONE;
    public int $contactPhone = self::CONTACT_OPTIONAL;
    public int $contactEmail = self::CONTACT_REQUIRED;
    public int $contactGender = self::CONTACT_NONE;

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
