<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * Represents contact page
 * @property \Tests\Support\AcceptanceTester $actor
 */
class SiteHomePage extends BasePage
{
    public string|array $route = 'consultation/home';
}
