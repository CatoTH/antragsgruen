<?php

declare(strict_types=1);

namespace app\models\api\motionType;

use app\models\db\{Consultation, ConsultationMotionType, ConsultationUserGroup};
use app\models\policies\{IPolicy, UserGroups};

class MotionTypePolicyUpdateRequest
{
    public function __construct(
        public MotionTypePolicyId $id,
        /** @var int[]|null */
        public ?array $userGroupIds = null,
    ) {
    }

    public function toPolicy(Consultation $consultation, ConsultationMotionType $motionType): IPolicy
    {
        $policy = IPolicy::getInstanceFromDb((string)$this->id->toPolicyInt(), $consultation, $motionType);
        if ($policy instanceof UserGroups) {
            $groups = ConsultationUserGroup::loadGroupsByIdForConsultation($consultation, $this->userGroupIds ?? []);
            $policy->setAllowedUserGroups($groups);
        }

        return $policy;
    }

    /**
     * @param array<string, mixed> $data {id: int|string, groups?: array<int|string>}
     */
    public static function fromPostData(array $data): self
    {
        return new self(
            id: MotionTypePolicyId::fromPolicyInt(intval($data['id'] ?? IPolicy::POLICY_NOBODY)),
            userGroupIds: isset($data['groups']) ? array_map('intval', $data['groups']) : null,
        );
    }

    public static function defaultAll(): self
    {
        return new self(id: MotionTypePolicyId::ALL);
    }
}
