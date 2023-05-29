<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * @property \Tests\Support\AcceptanceTester $actor
 */
class AdminAmendmentPage extends BasePage
{
    public string|array $route = 'admin/amendment/update';

    /**
     *
     */
    public function saveForm(): void
    {
        $this->actor->submitForm('#amendmentUpdateForm', [], 'save');
    }
}
