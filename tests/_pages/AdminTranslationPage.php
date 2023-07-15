<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * @property \Tests\Support\AcceptanceTester $actor
 */
class AdminTranslationPage extends BasePage
{
    public string|array $route = 'admin/index/translation';
}
