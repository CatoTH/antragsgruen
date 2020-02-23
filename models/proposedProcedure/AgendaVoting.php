<?php

namespace app\models\proposedProcedure;

use app\models\db\{Amendment, IMotion, Motion, VotingBlock};

class AgendaVoting
{
    /** @var string */
    public $title;

    /** @var VotingBlock|null */
    public $voting;

    /** @var IMotion[] */
    public $items = [];

    public function __construct(string $title, ?VotingBlock $voting)
    {
        $this->title  = $title;
        $this->voting = $voting;
    }

    public function getId(): string
    {
        if ($this->voting) {
            return $this->voting->id;
        } else {
            return 'new';
        }
    }

    public function getHandledMotionIds(): array
    {
        $ids = [];
        foreach ($this->items as $item) {
            if (is_a($item, Motion::class)) {
                $ids[] = $item->id;
            }
        }
        return $ids;
    }

    public function getHandledAmendmentIds(): array
    {
        $ids = [];
        foreach ($this->items as $item) {
            if (is_a($item, Amendment::class)) {
                $ids[] = $item->id;
            }
        }
        return $ids;
    }
}
