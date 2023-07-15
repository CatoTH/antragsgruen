<?php

namespace Tests\_pages;

use Tests\Support\Helper\BasePage;

/**
 * @property \Tests\Support\AcceptanceTester $actor
 */
class MotionCreatePage extends BasePage
{
    public string|array $route = 'motion/create';

    public function createMotion(string $title = 'Testantrag 1', bool $screeningNeeded = false): void
    {
        $this->fillInValidSampleData($title);
        $this->saveForm();
        $this->actor->see(mb_strtoupper('Antrag bestätigen'), 'h1');
        $this->actor->submitForm('#motionConfirmForm', [], 'confirm');
        if ($screeningNeeded) {
            $this->actor->see(mb_strtoupper('Antrag eingereicht'), 'h1');
        } else {
            $this->actor->see(mb_strtoupper('Antrag veröffentlicht'), 'h1');
        }
    }

    public function fillInValidSampleData(string $title = 'Testantrag 1'): void
    {
        $this->actor->fillField(['name' => 'sections[1]'], $title);
        $this->actor->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("<p><strong>Test</strong></p>");');
        $this->actor->executeJS('CKEDITOR.instances.sections_3_wysiwyg.setData("<p><strong>Test 2</strong></p>");');
        $this->actor->fillField('#initiatorPrimaryName', 'Mein Name');
        $this->actor->fillField('#initiatorEmail', 'test@example.org');
    }

    public function saveForm(): void
    {
        $this->actor->submitForm('#motionEditForm', [], 'save');
    }
}
