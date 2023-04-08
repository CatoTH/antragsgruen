<?php

declare(strict_types=1);

namespace app\models\votings;

class Answer implements \JsonSerializable
{
    public int $dbId;
    public string $apiId;
    public string $title;
    public ?int $statusId = null;

    public function jsonSerialize(): array
    {
        return [
            'api_id' => $this->apiId,
            'title' => $this->title,
            'status_id' => $this->statusId,
        ];
    }
}
