<?php

namespace app\tests\_pages;

use Helper\BasePage;

/**
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class AdminConsultationPage extends BasePage
{
    public $route = 'admin/index/consultation';

    public static $maintenanceCheckbox = '#maintenanceMode';

    public function selectAmendmentNumbering($numbering)
    {
        $this->actor->selectOption('#amendmentNumbering', $numbering);
    }

    /**
     *
     */
    public function saveForm()
    {
        $this->actor->submitForm('#consultationSettingsForm', [], 'save');
    }
}
