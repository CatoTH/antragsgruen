<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * Represents content page
 * @property \Tests\Support\AcceptanceTester $actor
 */
class ContentPage extends BasePage
{
    public string|array $route = 'pages/show-page';
}
