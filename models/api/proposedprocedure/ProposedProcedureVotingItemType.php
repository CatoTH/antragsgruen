<?php

declare(strict_types=1);

namespace app\models\api\proposedprocedure;

enum ProposedProcedureVotingItemType: string
{
    case MOTION = 'Motion';
    case AMENDMENT = 'Amendment';
}
