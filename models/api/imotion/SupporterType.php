<?php

declare(strict_types=1);

namespace app\models\api\imotion;

enum SupporterType: string
{
    case PERSON = 'person';
    case ORGANIZATION = 'organization';
}
