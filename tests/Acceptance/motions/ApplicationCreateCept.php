<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();



$I->wantTo('open the application form');

$I->gotoConsultationHome(false, 'parteitag', 'parteitag');

$I->see('1. Vorsitzende*r', '#agendaitem_3');
$I->seeElement('#agendaitem_3 > div > h3 .motionCreateLink');
$I->click('#agendaitem_3 > div > h3 .motionCreateLink');

$I->see(mb_strtoupper('Bewerben'), '.breadcrumb');
$I->see(mb_strtoupper('1. Vorsitzende*r: Bewerben'), 'h1');

$I->dontSee('Voraussetzungen für einen Antrag');
$I->see('Name', 'label');
$I->see('Foto', 'label');
$I->see('Angaben', 'label');
$I->see('Selbstvorstellung', 'label');




$I->wantTo('apply for a job');

$I->fillField('#sections_13', 'Jane Doe');

$I->attachFile('#sections_14', 'logo.png');
$I->fillField('#sections_15_1', '23');
$I->fillField('#sections_15_2', 'Female');
$I->fillField('#sections_15_3', 'Somewhere');
if (method_exists($I, 'executeJS')) {
    $I->executeJS('CKEDITOR.instances.sections_16_wysiwyg.setData("<p><strong>Test</strong></p>");');
} else {
    $I->see('JavaScript has to be enabled to perform this test');
}
$I->fillField('#initiatorPrimaryName', 'Jane Doe (2)');
$I->fillField('#initiatorEmail', 'jane@example.org');

$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->submitForm('#motionConfirmedForm', [], '');


$I->wantTo('check if my application is visible (should not be so)');
