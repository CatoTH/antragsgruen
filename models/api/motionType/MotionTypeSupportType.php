<?php

declare(strict_types=1);

namespace app\models\api\motionType;

enum MotionTypeSupportType: string
{
    case LIKE = 'like';
    case DISLIKE = 'dislike';
    case SUPPORT = 'support';

    public function toFlag(): int
    {
        return match ($this) {
            self::LIKE => \app\models\supportTypes\SupportBase::LIKEDISLIKE_LIKE,
            self::DISLIKE => \app\models\supportTypes\SupportBase::LIKEDISLIKE_DISLIKE,
            self::SUPPORT => \app\models\supportTypes\SupportBase::LIKEDISLIKE_SUPPORT,
        };
    }
}
