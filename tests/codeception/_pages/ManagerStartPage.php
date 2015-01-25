<?php

namespace tests\codeception\_pages;

use yii\codeception\BasePage;

/**
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class ManagerStartPage extends BasePage
{
    public $route = 'manager/index';
}
