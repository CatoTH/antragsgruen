<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * Represents login page
 * @property \Tests\Support\AcceptanceTester $actor
 */
class LoginPage extends BasePage
{
    public string|array $route = 'user/login';

    /**
     * @param string $username
     * @param string $password
     */
    public function login(string $username, string $password): void
    {
        $this->actor->fillField('input[name="LoginForm[username]"]', $username);
        $this->actor->fillField('input[name="LoginForm[password]"]', $password);
        $this->actor->click('login-button');
    }
}
