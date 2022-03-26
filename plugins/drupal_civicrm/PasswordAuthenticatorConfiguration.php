<?php

declare(strict_types=1);

namespace app\plugins\drupal_civicrm;

use app\models\settings\JsonConfigTrait;

class PasswordAuthenticatorConfiguration implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var string */
    public $pdoDsn = 'mysql:host=localhost;dbname=...;charset=utf8mb4';
    /** @var string */
    public $pdoUsername = '';
    /** @var string */
    public $pdoPassword = '';

    /** @var string */
    public $userGroup = '';
    /** @var int */
    public $domainId = 1;

    /** @var string */
    public $resetAlternativeLink = '';
}
