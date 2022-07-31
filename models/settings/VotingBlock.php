<?php

namespace app\models\settings;

class VotingBlock implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var int|null - timestamp when the voting was opened */
    public ?int $openedTs = null;
    /** @var null|int - in seconds */
    public ?int $votingTime = null;

    public function getAdminApiObject(): array
    {
        return [
            'opened_ts' => $this->openedTs,
            'voting_time' => $this->votingTime,
        ];
    }
}
