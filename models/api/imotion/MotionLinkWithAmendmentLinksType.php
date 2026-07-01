<?php

declare(strict_types=1);

namespace app\models\api\imotion;

enum MotionLinkWithAmendmentLinksType: string
{
    case MOTION = 'motion';
    case AMENDMENT = 'amendment';
}
