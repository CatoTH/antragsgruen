<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * @property \Tests\Support\AcceptanceTester $actor
 */
class ManagerStartPage extends BasePage
{
    public string|array $route = '/antragsgruen_sites/manager/index';
}
