<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * Represents login page
 * @property \Tests\Support\AcceptanceTester $actor
 */
class PasswordRecoveryPage extends BasePage
{
    public string|array $route = 'user/recovery';
}
