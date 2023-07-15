<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * @property \Tests\Support\AcceptanceTester $actor
 */
class AdminUsersPage extends BasePage
{
    public string|array $route = 'admin/users/index';
}
