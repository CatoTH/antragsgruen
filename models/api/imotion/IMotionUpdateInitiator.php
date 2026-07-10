<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\components\Tools;
use app\models\exceptions\FormError;
use app\models\db\{ConsultationMotionType, ISupporter, User};
use app\models\settings\{PrivilegeQueryContext, Privileges};
use Symfony\Component\Serializer\Attribute\Ignore;

class IMotionUpdateInitiator
{
    // Not part of the public API schema and must never be deserialized from a request body:
    // attributing an initiator to another user requires PRIVILEGE_MOTION_INITIATORS (enforced in SupportBase::buildInitiatorsFromDto).
    #[Ignore]
    public ?int $userId = null; // Only to be set by web request for now, by admins

    public function __construct(
        public SupporterType $personType,
        public string $name,
        public ?int $id = null,
        public ?string $organization = null,
        public ?string $contactName = null,
        public ?string $contactEmail = null,
        public ?string $contactPhone = null,
        public ?string $gender = null,
        public ?string $resolutionDate = null,
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @param class-string<ISupporter> $supporterClass
     */
    public static function fromPostData(ConsultationMotionType $motionType, array $post, string $supporterClass): self
    {
        $postInitiator = $post['Initiator'];
        $othersPrivilege = User::havePrivilege($motionType->getConsultation(), Privileges::PRIVILEGE_MOTION_INITIATORS, PrivilegeQueryContext::motionType($motionType->id));

        $personType = ((int)($postInitiator['personType'] ?? SupporterType::PERSON) === ISupporter::PERSON_ORGANIZATION)
            ? SupporterType::ORGANIZATION
            : SupporterType::PERSON;

        $initiator = new self(
            id: isset($postInitiator['id']) && $postInitiator['id'] > 0 ? intval($postInitiator['id']) : null,
            personType: $personType,
            name: $postInitiator['primaryName'] ?? '',
            organization: $postInitiator['organization'] ?? null,
            contactName: $postInitiator['contactName'] ?? null,
            contactEmail: $postInitiator['contactEmail'] ?? null,
            contactPhone: $postInitiator['contactPhone'] ?? null,
            gender: $postInitiator['gender'] ?? null,
            resolutionDate: Tools::dateBootstrapdate2sql($postInitiator['resolutionDate'] ?? null),
        );

        if ($othersPrivilege && isset($post['otherInitiator'])) {
            $setType = $post['initiatorSetType'] ?? '';
            $setUsername = $post['initiatorSetUsername'] ?? '';
            if (isset($post['initiatorSet']) && $post['initiatorSet'] === '1') {
                if (trim($setUsername) !== '') {
                    $user = User::findByAuthTypeAndName($setType, $setUsername);
                    if (!$user) {
                        throw new FormError(\Yii::t('motion', 'err_user_not_found'));
                    }
                    $initiator->userId = $user->id;
                } else {
                    $initiator->userId = null;
                }
            } else {
                if ($initiator->id) {
                    $supporter = $supporterClass::findOne($initiator->id);
                    $initiator->userId = $supporter?->userId;
                } else {
                    $initiator->userId = null;
                }
            }
        } else {
            $initiator->userId = User::getCurrentUser()?->id;
        }

        return $initiator;
    }

    /**
     * @param array<string, mixed> $post
     * @return self[]
     */
    public static function moreFromPostData(array $post): array
    {
        $moreInitiators = [];

        $postInitiator = $post['moreInitiators'] ?? ['name' => [], 'organization' => []];
        foreach (array_keys($postInitiator['name']) as $i) {
            $moreInitiators[] = new self(
                id: isset($postInitiator['id'][$i]) && $postInitiator['id'][$i] > 0 ? intval($postInitiator['id'][$i]) : null,
                personType: SupporterType::PERSON,
                name: $postInitiator['name'][$i] ?? '',
                organization: $postInitiator['organization'][$i] ?? null,
            );
        }

        return $moreInitiators;
    }
}
