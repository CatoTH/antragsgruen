<?php

declare(strict_types=1);

namespace app\plugins\drupal_civicrm;

use app\models\settings\JsonConfigTrait;

class PasswordAuthenticatorConfiguration implements \JsonSerializable
{
    use JsonConfigTrait;

    public $pdoDsn = 'mysql:host=localhost;dbname=...;charset=utf8mb4';
    public $pdoUsername = '';
    public $pdoPassword = '';

    public $userGroup = '';
    public $domainId = 1;

    public $resetAlternativeLink = '';
}
