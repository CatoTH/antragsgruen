<?php

namespace tests\codeception\_pages;

use yii\codeception\BasePage;

/**
 * Represents contact page
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class ConsultationHomePage extends BasePage
{
    public $route = 'consultation/index';
}
