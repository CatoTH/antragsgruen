<?php

declare(strict_types=1);

namespace app\models\api\motionType;

enum MotionTypePolicyId: string
{
    case NOBODY = 'nobody';
    case ALL = 'all';
    case LOGGED_IN = 'logged_in';
    case ADMINS = 'admins';
    case USER_GROUPS = 'user_groups';
}
