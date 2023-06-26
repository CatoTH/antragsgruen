<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * @property \Tests\Support\AcceptanceTester $actor
 */
class VotingAdminPage extends BasePage
{
    public string|array $route = 'consultation/admin-votings';
}
