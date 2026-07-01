<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\models\db\{ConsultationMotionType, ISupporter, User};
use app\models\settings\{PrivilegeQueryContext, Privileges};

class IMotionUpdateInitiator
{
    public ?int $userId = null; // Only to be set by web request for now, by admins

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

    /** @param array<string, mixed> $post */
    public static function fromPostData(ConsultationMotionType $motionType, array $post): self
    {
        $postInitiator = $post['Initiator'];
        $othersPrivilege = User::havePrivilege($motionType->getConsultation(), Privileges::PRIVILEGE_MOTION_INITIATORS, PrivilegeQueryContext::motionType($motionType->id));

        $personType = ((int)($postInitiator['personType'] ?? SupporterType::PERSON) === ISupporter::PERSON_ORGANIZATION)
            ? SupporterType::ORGANIZATION
            : SupporterType::PERSON;

        $initiator = new self(
            personType: $personType,
            name: $postInitiator['primaryName'] ?? '',
            organization: $postInitiator['organization'] ?? null,
            contactName: $postInitiator['contactName'] ?? null,
            contactEmail: $postInitiator['contactEmail'] ?? null,
            contactPhone: $postInitiator['contactPhone'] ?? null,
            gender: $postInitiator['gender'] ?? null,
            resolutionDate: $postInitiator['resolutionDate'] ?? null,
        );


        $initiator->userId = null;
        if (!(isset($post['otherInitiator']) && $othersPrivilege)) {
            $initiator->userId = User::getCurrentUser()?->id;
        }

        return $initiator;
    }
}
