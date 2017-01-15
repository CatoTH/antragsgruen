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
    public function getEmailAdminText();

    /**
     * @return string
     */
    public function getEmailAdminTitle();
}
