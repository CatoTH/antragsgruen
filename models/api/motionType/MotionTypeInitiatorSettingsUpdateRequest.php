<?php

declare(strict_types=1);

namespace app\models\api\motionType;

use app\models\settings\InitiatorForm;

class MotionTypeInitiatorSettingsUpdateRequest
{
    public function __construct(
        public MotionTypeInitiatorSettingsUpdateRequestType $type,
        public bool $initiatorCanBePerson,
        public bool $initiatorCanBeOrganization,
        public MotionTypePolicyUpdateRequest $personPolicy,
        public MotionTypePolicyUpdateRequest $organizationPolicy,
        public int $minSupporters,
        public bool $allowMoreSupporters,
        public bool $allowSupportingAfterPublication,
        public bool $offerNonPublicSupports,
        public bool $hasOrganizations,
        public MotionTypeContactRequirement $contactName,
        public MotionTypeContactRequirement $contactEmail,
        public MotionTypeContactRequirement $contactPhone,
        public MotionTypeContactRequirement $contactGender,
        public MotionTypeContactRequirement $hasResolutionDate,
        public ?int $minSupportersFemale = null,
    ) {
    }

    /**
     * @param array<string, mixed> $settingsPost The raw "(amendment)InitiatorSettings" POST array
     */
    public static function fromWebRequest(
        array $settingsPost,
        bool $initiatorCanBePerson,
        bool $initiatorCanBeOrganization,
        MotionTypePolicyUpdateRequest $personPolicy,
        MotionTypePolicyUpdateRequest $organizationPolicy,
    ): self {
        $minSupportersFemale = null;
        if (isset($settingsPost['minSupportersFemale']) && is_numeric($settingsPost['minSupportersFemale']) && $settingsPost['minSupportersFemale'] >= 0) {
            $minSupportersFemale = intval($settingsPost['minSupportersFemale']);
        }

        return new self(
            type: MotionTypeInitiatorSettingsUpdateRequestType::fromSupportBaseValue(intval($settingsPost['type'] ?? 0)),
            initiatorCanBePerson: $initiatorCanBePerson,
            initiatorCanBeOrganization: $initiatorCanBeOrganization,
            personPolicy: $personPolicy,
            organizationPolicy: $organizationPolicy,
            minSupporters: intval($settingsPost['minSupporters'] ?? 0),
            allowMoreSupporters: !empty($settingsPost['allowMoreSupporters']),
            allowSupportingAfterPublication: !empty($settingsPost['allowSupportingAfterPublication']),
            offerNonPublicSupports: !empty($settingsPost['offerNonPublicSupports']),
            hasOrganizations: !empty($settingsPost['hasOrganizations']),
            contactName: MotionTypeContactRequirement::fromDbValue(intval($settingsPost['contactName'] ?? InitiatorForm::CONTACT_NONE)),
            contactEmail: MotionTypeContactRequirement::fromDbValue(intval($settingsPost['contactEmail'] ?? InitiatorForm::CONTACT_REQUIRED)),
            contactPhone: MotionTypeContactRequirement::fromDbValue(intval($settingsPost['contactPhone'] ?? InitiatorForm::CONTACT_OPTIONAL)),
            contactGender: MotionTypeContactRequirement::fromDbValue(intval($settingsPost['contactGender'] ?? InitiatorForm::CONTACT_NONE)),
            hasResolutionDate: MotionTypeContactRequirement::fromDbValue(intval($settingsPost['hasResolutionDate'] ?? InitiatorForm::CONTACT_OPTIONAL)),
            minSupportersFemale: $minSupportersFemale,
        );
    }
}
