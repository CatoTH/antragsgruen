<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * @property \Tests\Support\AcceptanceTester $actor
 */
class AdminConsultationPage extends BasePage
{
    public string|array $route = 'admin/index/consultation';

    public static string $maintenanceCheckbox = '#maintenanceMode';

    public function selectAmendmentNumbering($numbering): void
    {
        $this->actor->selectOption('#amendmentNumbering', $numbering);
    }

    /**
     *
     */
    public function saveForm(): void
    {
        $this->actor->submitForm('#consultationSettingsForm', [], 'save');
    }
}
