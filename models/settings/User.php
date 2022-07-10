<?php

namespace app\models\settings;

class User implements \JsonSerializable
{
    use JsonConfigTrait;

    public string $ppReplyTo = '';
}
