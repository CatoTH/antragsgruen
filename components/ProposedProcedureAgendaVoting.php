<?php

namespace app\components;

use app\models\db\Amendment;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\db\VotingBlock;

class ProposedProcedureAgendaVoting
{
    /** @var string */
    public $title;

    /** @var VotingBlock|null */
    public $voting;

    /** @var IMotion[] */
    public $items = [];

    /**
     * ProposedProcedureAgendaVoting constructor.
     *
     * @param string $title
     * @param VotingBlock|null $voting
     */
    public function __construct($title, $voting)
    {
        $this->title  = $title;
        $this->voting = $voting;
    }

    /**
     * @return array
     */
    public function getHandledMotionIds()
    {
        $ids = [];
        foreach ($this->items as $item) {
            if (is_a($item, Motion::class)) {
                $ids[] = $item->id;
            }
        }
        return $ids;
    }

    /**
     * @return array
     */
    public function getHandledAmendmentIds()
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
