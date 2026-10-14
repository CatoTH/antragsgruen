<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * @property \Tests\Support\AcceptanceTester $actor
 */
class AdminAppearancePage extends BasePage
{
    public string|array $route = 'admin/index/appearance';

    public function saveForm(): void
    {
        $this->actor->submitForm('#consultationAppearanceForm', [], 'save');
        $this->actor->waitForJS('return document.readyState === "complete"', 10);
    }
}
