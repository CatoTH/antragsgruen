<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\models\db\ISupporter;

class MotionCreateInitiator
{
    public function __construct(
        public SupporterType $personType,
        public string $name,
        public ?string $organization = null,
        public ?string $contactName = null,
        public ?string $contactEmail = null,
        public ?string $contactPhone = null,
        public ?string $gender = null,
        public ?string $resolutionDate = null,
    ) {
    }

    /** @param array<string, mixed> $postInitiator */
    public static function fromPostData(array $postInitiator): self
    {
        $personType = ((int)($postInitiator['personType'] ?? 0) === ISupporter::PERSON_ORGANIZATION)
            ? SupporterType::ORGANIZATION
            : SupporterType::PERSON;

        return new self(
            personType: $personType,
            name: $postInitiator['primaryName'] ?? '',
            organization: $postInitiator['organization'] ?? null,
            contactName: $postInitiator['contactName'] ?? null,
            contactEmail: $postInitiator['contactEmail'] ?? null,
            contactPhone: $postInitiator['contactPhone'] ?? null,
            gender: $postInitiator['gender'] ?? null,
            resolutionDate: $postInitiator['resolutionDate'] ?? null,
        );
    }
}
