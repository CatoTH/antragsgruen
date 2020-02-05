<?php

namespace app\models\notifications;

use app\models\db\User;

interface IEmailUser
{
    public function getEmailUser(): User;
    public function getEmailUserSubject(): string;
    public function getEmailUserText(): string;
    public function getEmailUserType(): int;
}
