<?php

namespace app\models\settings;

class User implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var string */
    public $ppReplyTo = '';
}
