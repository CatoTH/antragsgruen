<?php

declare(strict_types=1);

namespace app\models\votings;

class Answer implements \JsonSerializable
{
    /** @var string */
    public $id;

    /** @var string */
    public $title;

    /** @var null|int */
    public $statusId = null;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status_id' => $this->statusId,
        ];
    }
}
