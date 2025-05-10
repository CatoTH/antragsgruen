<?php

namespace app\models\settings;

class VotingBlock implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var int|null - timestamp when the voting was opened */
    public ?int $openedTs = null;
    /** @var null|int - in seconds */
    public ?int $votingTime = null;

    public const VOTES_NAMES_AUTH = 0;
    public const VOTES_NAMES_NAME = 1;
    public const VOTES_NAMES_ORGANIZATION = 2;

    public int $votesNames = self::VOTES_NAMES_AUTH;

    /** @var array<array{groupId: int|null, maxVotes: int}>|null */
    public ?array $maxVotesByGroup = null;
}
