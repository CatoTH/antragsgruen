<?php

declare(strict_types=1);

namespace app\models\api\motionType;

use app\models\policies\IPolicy;

enum MotionTypePolicyId: string
{
    case NOBODY = 'nobody';
    case ALL = 'all';
    case LOGGED_IN = 'logged_in';
    case ADMINS = 'admins';
    case USER_GROUPS = 'user_groups';

    public static function fromPolicyInt(int $id): self
    {
        return match ($id) {
            IPolicy::POLICY_NOBODY => self::NOBODY,
            IPolicy::POLICY_ALL => self::ALL,
            IPolicy::POLICY_LOGGED_IN => self::LOGGED_IN,
            IPolicy::POLICY_ADMINS => self::ADMINS,
            IPolicy::POLICY_USER_GROUPS => self::USER_GROUPS,
            default => throw new \InvalidArgumentException('Unknown policy id: ' . $id),
        };
    }

    public function toPolicyInt(): int
    {
        return match ($this) {
            self::NOBODY => IPolicy::POLICY_NOBODY,
            self::ALL => IPolicy::POLICY_ALL,
            self::LOGGED_IN => IPolicy::POLICY_LOGGED_IN,
            self::ADMINS => IPolicy::POLICY_ADMINS,
            self::USER_GROUPS => IPolicy::POLICY_USER_GROUPS,
        };
    }
}
