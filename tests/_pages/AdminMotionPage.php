<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * @property \Tests\Support\AcceptanceTester $actor
 */
class AdminMotionPage extends BasePage
{
    public string|array $route = 'admin/motion/update';

    /**
     *
     */
    public function saveForm(): void
    {
        $this->actor->submitForm('#motionUpdateForm', [], 'save');
    }
}
