<?php

declare(strict_types=1);

namespace app\models\settings;

class ConsultationUserGroup implements \JsonSerializable
{
    use JsonConfigTrait;

    public const PERMISSION_PROPOSED_PROCEDURE = 'proposed-procedure';
    public const PERMISSION_ADMIN_ALL = 'admin-all';
    public const PERMISSION_ADMIN_SPEECH_LIST = 'admin-speech-list';

    /** @var string[] */
    public $permissions;

    /** @var int */
    public $templateId = null;
}
