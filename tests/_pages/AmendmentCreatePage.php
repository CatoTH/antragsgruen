<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * @property \Tests\Support\AcceptanceTester $actor
 */
class AmendmentCreatePage extends BasePage
{
    public string|array $route = 'amendment/create';

    public function createAmendment(string $title, bool $isPublishedImmediately): void
    {
        $this->fillInValidSampleData($title);
        $this->saveForm();
        $this->actor->see(mb_strtoupper('Antrag bestätigen'), 'h1');
        $this->actor->submitForm('#amendmentConfirmForm', [], 'confirm');
        if ($isPublishedImmediately) {
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
