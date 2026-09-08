<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingOrganizationResult
{
    public function __construct(
        /** @var VotingAnswerCount[] */
        public array $answers,
        public ?string $organization = null,
    ) {
    }
}
