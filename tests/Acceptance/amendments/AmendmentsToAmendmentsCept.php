<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enable amendments to amendments');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->dontSeeElement('#sidebar .amendmentCreate');
$page = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->checkOption('#allowAmendmentsToAmendments');
$page->saveForm();


$I->wantTo('create an amendment to an amendment');
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->click('#sidebar .amendmentCreate');

$I->wait(0.3);
$I->see('A small replacement', '#sections_2_wysiwyg .ice-ins');
$I->see('At vero', '#sections_2_wysiwyg .ice-del');
$I->dontSee('The first amendment');

$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData().replace(/Stet clita kasd gubergren/, "Test 12345678"))');
$I->executeJs('CKEDITOR.instances.amendmentReason_wysiwyg.setData("The follow-up amendment");');
$I->fillField('#initiatorPrimaryName', 'A new person');
$I->fillField('#initiatorEmail', 'test@example.org');

$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');


$I->wantTo('see the new amendment');
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->see('Ä5', '.amendments .amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->click('.amendments .amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->see('Ä1', '.amendingAmendmentRow');
$I->see('Test 12345678', 'ins');
$I->see('A small replacement', 'ins');
$I->see('The follow-up amendment');
