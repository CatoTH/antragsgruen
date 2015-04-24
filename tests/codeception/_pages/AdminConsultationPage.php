<?php

namespace tests\codeception\_pages;

use yii\codeception\BasePage;

/**
 * Represents contact page
 * @property \AntragsgruenAcceptenceTester|\FunctionalTester $actor
 */
class AdminConsultationPage extends BasePage
{
    public $route = 'admin/index/consultation';

    public static $maintainanceCheckbox = '#maintainanceMode';
}
