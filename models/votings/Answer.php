<?php

declare(strict_types=1);

namespace app\models\votings;

class Answer implements \JsonSerializable
{
    /** @var int */
    public $dbId;

    /** @var string */
    public $apiId;

    /** @var string */
    public $title;

    /** @var null|int */
    public $statusId = null;

    public function jsonSerialize(): array
    {
        return [
            'api_id' => $this->apiId,
            'title' => $this->title,
            'status_id' => $this->statusId,
        ];
    }
}
