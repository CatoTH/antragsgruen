<?php

declare(strict_types=1);

namespace app\models\api\proposedprocedure;

use app\models\db\Motion;

class MotionProposedProcedure
{
    public function __construct(
        public ?int $statusId = null,
        public ?string $statusTitle = null,
    ) {
    }

    public static function fromMotion(Motion $motion): ?self
    {
        $proposal = $motion->getLatestProposal();
        if (!$proposal->isProposalPublic() || !$proposal->proposalStatus) {
            return null;
        }

        return new self(
            statusId: $proposal->proposalStatus,
            statusTitle: $proposal->getFormattedProposalStatus(true),
        );
    }
}
