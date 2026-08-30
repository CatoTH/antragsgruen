<?php

namespace app\models\settings;

class VotingBlock implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var int|null - timestamp when the voting was opened */
    public ?int $openedTs = null;

    /**
     * The publicity of the single votes at the time this voting was opened - the promise its voters
     * are acting on. A vote is never stored more public than this, so widening the setting of a
     * running voting cannot expose votes that were cast while it was still secret. Cleared when the
     * voting is reset, which is when a new round with new terms begins.
     */
    public ?int $votesPublicAtOpening = null;
    /** @var null|int - in seconds */
    public ?int $votingTime = null;

    public const VOTES_NAMES_AUTH = 0;
    public const VOTES_NAMES_NAME = 1;
    public const VOTES_NAMES_ORGANIZATION = 2;

    public int $votesNames = self::VOTES_NAMES_AUTH;

    /** @var array<array{groupId: int|null, maxVotes: int}>|null */
    public ?array $maxVotesByGroup = null;
}
