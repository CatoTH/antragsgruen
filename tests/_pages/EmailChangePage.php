<?php

namespace app\tests\_pages;

use Helper\BasePage;

/**
 * Represents contact page
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class EmailChangePage extends BasePage
{
    public $route = 'user/emailchange';
}
