<?php

declare(strict_types=1);

namespace app\models;

class UserOrganization
{
    // No user groups are defined by default.
    // However, to avoid collisions with future potential database implementations, it is encouraged for plugin-defined
    // groups to use negative IDs if numerical.

    /** @var string */
    public $id;

    /** @var string */
    public $title;

    public function __construct(string $id, string $title)
    {
        $this->id = $id;
        $this->title = $title;
    }
}
