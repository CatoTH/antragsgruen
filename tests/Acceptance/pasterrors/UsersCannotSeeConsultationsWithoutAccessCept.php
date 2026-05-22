<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('prepare the test case');

$I->loginAndGotoStdAdminPage();
$I->click('.siteConsultationsLink');
$I->fillField('#newTitle', 'Test3');
$I->fillField('#newShort', 'test3');
$I->fillField('#newPath', 'test3');
$I->submitForm('.consultationCreateForm', [], 'createConsultation');
$I->logout();

$I->wantTo('See both consultations as user');
$I->loginAsStdUser();
$I->gotoConsultationHome();
$I->click('#myAccountLink');
$I->seeElement('.notificationLinks .consultation1');
$I->seeElement('.notificationLinks .consultation' . AcceptanceTester::FIRST_FREE_CONSULTATION_ID);
$I->logout();


$I->wantTo('Restrict access to consultations');
$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('.forceLogin input');
$I->checkOption('.managedUserAccounts input');
$page->saveForm();
$I->logout();


$I->wantTo('See only one consultations as user');
$I->loginAsStdUser();
$I->gotoConsultationHome(false);
$I->seeElement('.noAccessAlert');
$I->click('#myAccountLink');
$I->dontSeeElement('.notificationLinks .consultation1');
$I->seeElement('.notificationLinks .consultation' . AcceptanceTester::FIRST_FREE_CONSULTATION_ID);
