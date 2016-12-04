<?php

namespace app\tests\_pages;

use Helper\BasePage;

/**
 * Represents login page
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class PasswordRecoveryPage extends BasePage
{
    public $route = 'user/recovery';
}
