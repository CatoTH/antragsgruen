<?php

declare(strict_types=1);

namespace app\plugins\drupal_civicrm;

use app\models\settings\JsonConfigTrait;

class PasswordAuthenticatorConfiguration implements \JsonSerializable
{
    use JsonConfigTrait;

    public string $pdoDsn = 'mysql:host=localhost;dbname=...;charset=utf8mb4';
    public string $pdoUsername = '';
    public string $pdoPassword = '';

    public string $userGroup = '';
    public int $domainId = 1;

    public string $resetAlternativeLink = '';
}
