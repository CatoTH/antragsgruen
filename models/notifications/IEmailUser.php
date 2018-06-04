<?php

namespace app\models\notifications;

use app\models\db\User;

/**
 * Interface IEmailUser
 * @package app\models\notifications
 */
interface IEmailUser
{
    /**
     * @return User
     */
    public function getEmailUser();

    /**
     * @return string
     */
    public function getEmailUserSubject();

    /**
     * @return string
     */
    public function getEmailUserText();

    /**
     * @return int
     */
    public function getEmailUserType();
}
