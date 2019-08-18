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

    /**
     * @return boolean
     */
    public function hasAnyData()
    {
        return $this->votesYes || $this->votesNo || $this->votesInvalid || $this->votesAbstention || $this->comment;
    }

    /**
     * @param array $votes
     */
    public function setFromPostData($votes)
    {
        if (isset($votes['yes']) && is_numeric($votes['yes'])) {
            $this->votesYes = IntVal($votes['yes']);
        }
        if (isset($votes['no']) && is_numeric($votes['no'])) {
            $this->votesNo = IntVal($votes['no']);
        }
        if (isset($votes['abstention']) && is_numeric($votes['abstention'])) {
            $this->votesAbstention = IntVal($votes['abstention']);
        }
        if (isset($votes['invalid']) && is_numeric($votes['invalid'])) {
            $this->votesInvalid = IntVal($votes['invalid']);
        }
        if (isset($votes['comment'])) {
            $this->comment = $votes['comment'];
        }
    }
}
