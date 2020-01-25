<?php

namespace app\tests\_pages;

use Helper\BasePage;

/**
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class AdminAppearancePage extends BasePage
{
    public $route = 'admin/index/appearance';

    /**
     *
     */
    public function saveForm()
    {
        $this->actor->submitForm('#consultationAppearanceForm', [], 'save');
    }
}
