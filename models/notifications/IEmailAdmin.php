<?php

namespace app\models\notifications;

/**
 * Interface IEmailAdmin
 * @package app\models\notifications
 */
interface IEmailAdmin
{
    /**
     * @return string
     */
    public function getEmailAdminSubject();

    /**
     * @return string
     */
    public function getEmailAdminText();
}
