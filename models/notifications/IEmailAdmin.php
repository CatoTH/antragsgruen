<?php

namespace app\models\notifications;

interface IEmailAdmin
{
    public function getEmailAdminSubject(): string;
    public function getEmailAdminText(): string;
}
