<?php

namespace app\models\settings;

class VotingData implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var null|int */
    public $votesYes = null;
    /** @var null|int */
    public $votesNo = null;
    /** @var null|int */
    public $votesAbstention = null;
    /** @var null|int */
    public $votesInvalid = null;

    /** @var null|string */
    public $comment = null;
}
