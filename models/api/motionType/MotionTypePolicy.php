<?php

declare(strict_types=1);

namespace app\models\api\motionType;

use app\models\db\ConsultationMotionType;
use app\models\policies\{IPolicy, UserGroups};

class MotionTypePolicy
{
    public function __construct(
        public MotionTypePolicyId $id,
        public string $description,
        /** @var MotionTypeDeadlineEntry[] */
        public array $deadlines,
        /** @var int[]|null */
        public ?array $userGroupIds = null,
    ) {
    }

    /** @param MotionTypeDeadlineEntry[] $deadlines */
    public static function fromPolicy(IPolicy $policy, array $deadlines): self
    {
        $userGroupIds = null;
        if ($policy instanceof UserGroups) {
            $userGroupIds = array_values(array_map(
                fn(\app\models\db\ConsultationUserGroup $group) => $group->id,
                $policy->getAllowedUserGroups()
            ));
        }

        return new self(
            id: MotionTypePolicyId::fromPolicyInt($policy::getPolicyID()),
            description: $policy::getPolicyName(),
            deadlines: $deadlines,
            userGroupIds: $userGroupIds,
        );
    }
}
