<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * Represents contact page
 * @property \Tests\Support\AcceptanceTester $actor
 */
class EmailChangePage extends BasePage
{
    public string|array $route = 'user/emailchange';
}
