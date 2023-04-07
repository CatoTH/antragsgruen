<?php

namespace app\models\settings;

class VotingBlock implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var int|null - timestamp when the voting was opened */
    public ?int $openedTs = null;
    /** @var null|int - in seconds */
    public ?int $votingTime = null;

    /** @var array<array{groupId: int|null, maxVotes: int}>|null */
    public ?array $maxVotesByGroup = null;
}
