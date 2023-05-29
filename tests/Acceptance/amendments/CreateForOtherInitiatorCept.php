<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check that this option is not available for normal users');

$createPage = $I->gotoConsultationHome()->gotoAmendmentCreatePage(2);
$I->dontSeeElement('input[name=otherInitiator]');

$I->wantTo('check that it is available as an admin (and preselected)');
$I->loginAsStdAdmin();
$I->seeElement('input[name=otherInitiator]');
$I->seeCheckboxIsChecked('input[name=otherInitiator]');
$I->seeInField('#initiatorPrimaryName', '');
$I->seeInField('#initiatorEmail', '');


$I->wantTo('create an amendment as another user');
$createPage->fillInValidSampleData('Neuer Testantrag 1');
$createPage->saveForm();
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$I->see(mb_strtoupper('Änderungsantrag veröffentlicht'), 'h1');
$I->submitForm('#motionConfirmedForm', [], '');
$I->gotoAmendment(true, 2, AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->dontSeeElement('.sidebarActions .withdraw');
$I->dontSeeElement('.sidebarActions .edit');



$I->wantTo('create an amendment as myself');
$createPage = $I->gotoConsultationHome()->gotoAmendmentCreatePage(2);
$I->uncheckOption('input[name=otherInitiator]');
$createPage->fillInValidSampleData('Neuer Testantrag 2');
$createPage->saveForm();
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$I->see(mb_strtoupper('Änderungsantrag veröffentlicht'), 'h1');
$I->submitForm('#motionConfirmedForm', [], '');
$I->gotoAmendment(true, 2, AcceptanceTester::FIRST_FREE_AMENDMENT_ID + 1);
$I->seeElement('.sidebarActions .withdraw');
$I->dontSeeElement('.sidebarActions .edit');



$I->wantTo('enable amendment editing for initiators');
$I->gotoStdAdminPage()->gotoConsultation();
$I->checkOption('#iniatorsMayEdit');
$I->submitForm('#consultationSettingsForm', [], 'save');
$I->gotoAmendment(true, 2, AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->dontSeeElement('.sidebarActions .withdraw');
$I->dontSeeElement('.sidebarActions .edit');

$I->gotoAmendment(true, 2, AcceptanceTester::FIRST_FREE_AMENDMENT_ID + 1);
$I->seeElement('.sidebarActions .withdraw');
$I->seeElement('.sidebarActions .edit');

$I->click('.sidebarActions .edit a');
$I->dontSeeCheckboxIsChecked('input[name=otherInitiator]');
