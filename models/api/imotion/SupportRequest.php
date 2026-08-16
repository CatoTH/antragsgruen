<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\models\db\ISupporter;
use app\models\db\User;
use app\models\exceptions\FormError;
use app\models\settings\InitiatorForm;
use app\models\supportTypes\SupportBase;

class SupportRequest
{
    public function __construct(
        public string $name,
        public ?SupporterType $personType = null,
        public ?string $organization = null,
        public ?string $gender = null,
        public ?bool $nonPublic = null,
    ) {
    }

    /**
     * @param array<string, mixed> $post
     */
    public static function fromWebRequest(array $post, ?User $user, SupportBase $supportType): self
    {
        if ($user && ($user->fixedData & User::FIXED_NAME)) {
            $name = $user->name;
        } else {
            $name = (string)($post['motionSupportName'] ?? '');
        }
        if ($user && ($user->fixedData & User::FIXED_ORGA)) {
            $orga = $user->organization;
        } else {
            $orga = (string)($post['motionSupportOrga'] ?? '');
        }
        $gender = (string)($post['motionSupportGender'] ?? '');
        $nonPublic = ($supportType->getSettingsObj()->offerNonPublicSupports && !array_key_exists('motionSupportPublic', $post));
        $personType = (intval($post['motionSupportPersonType'] ?? ISupporter::PERSON_NATURAL) === ISupporter::PERSON_ORGANIZATION ?
            SupporterType::ORGANIZATION : SupporterType::PERSON);

        return new self(
            name: $name,
            personType: $personType,
            organization: $orga,
            gender: ($gender !== '' ? $gender : null),
            nonPublic: $nonPublic,
        );
    }

    public function getPersonTypeDb(): int
    {
        return ($this->personType === SupporterType::ORGANIZATION ? ISupporter::PERSON_ORGANIZATION : ISupporter::PERSON_NATURAL);
    }

    /**
     * Validates the request against the given support type's settings and normalizes the gender value.
     * @throws FormError
     */
    public function validate(SupportBase $supportType): void
    {
        $settings = $supportType->getSettingsObj();

        $asOrganization = ($this->personType === SupporterType::ORGANIZATION);
        if ($asOrganization && !$settings->supporterCanBeOrganization) {
            throw new FormError('Supporting as an organization is not possible');
        }
        // If neither is allowed, this is a misconfiguration and supporting as a natural person is assumed
        if (!$asOrganization && !$settings->supporterCanBePerson && $settings->supporterCanBeOrganization) {
            throw new FormError('Supporting as a natural person is not possible');
        }

        // As an organization, the organization is the supporter itself, hence it is always required
        if (($asOrganization || $settings->hasOrganizations) && trim($this->organization ?? '') === '') {
            throw new FormError('No organization entered');
        }
        if (trim($this->name) === '') {
            throw new FormError('You need to enter a name');
        }

        // The gender only refers to natural persons
        if ($asOrganization) {
            $this->gender = null;
            return;
        }

        $validGenderKeys = array_keys(SupportBase::getGenderSelection());
        if ($settings->contactGender === InitiatorForm::CONTACT_REQUIRED && !in_array($this->gender, $validGenderKeys)) {
            throw new FormError('You need to fill the gender field');
        }
        if (!in_array($this->gender, $validGenderKeys)) {
            $this->gender = null;
        }
    }
}
