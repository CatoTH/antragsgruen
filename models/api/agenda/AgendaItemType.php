<?php

declare(strict_types=1);

namespace app\models\api\agenda;

enum AgendaItemType: string
{
    case ITEM = 'item';
    case DATE_SEPARATOR = 'date_separator';
}
