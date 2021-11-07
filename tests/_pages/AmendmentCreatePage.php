<?php

namespace app\tests\_pages;

use Helper\BasePage;

/**
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class AmendmentCreatePage extends BasePage
{
    public $route = 'amendment/create';

    public function createAmendment(string $title, bool $isPublishedImmediatelly): void
    {
        $this->fillInValidSampleData($title);
        $this->saveForm();
        $this->actor->see(mb_strtoupper('Antrag bestätigen'), 'h1');
        $this->actor->submitForm('#amendmentConfirmForm', [], 'confirm');
        if ($isPublishedImmediatelly) {
            $this->actor->see(mb_strtoupper('Änderungsantrag veröffentlicht'), 'h1');
        } else {
            $this->actor->see(mb_strtoupper('Antrag eingereicht'), 'h1');
        }
    }

    public function fillInValidSampleData(string $title = 'Neuer Testantrag 1'): void
    {
        $this->actor->wait(1);
        $this->actor->fillField('#initiatorPrimaryName', 'Mein Name');
        $this->actor->fillField('#initiatorEmail', 'test@example.org');
        $this->actor->fillField('#sections_1', $title);
    }

    public function saveForm(): void
    {
        $this->actor->submitForm('#amendmentEditForm', [], 'save');
    }
}
