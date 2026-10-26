<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enable amendments to amendments in single paragraph mode');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$page = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->checkOption('#allowAmendmentsToAmendments');
$I->checkOption('#typeAmendSinglePara');
$page->saveForm();


$I->wantTo('create an amendment to an amendment');
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->click('#sidebar .amendmentCreate');
$I->wait(0.5);

// The paragraph changed by the amended amendment is the baseline, not a change of the new amendment
$I->see('A small replacement', '#section_holder_2_1');
$I->dontSeeElement('#section_holder_2_1 .ice-ins');
$I->dontSeeElement('.single-paragraph.modified');
$I->seeElement('#section_holder_2_1.modifyable');
$I->seeElement('#section_holder_2_3.modifyable');

$I->click('#section_holder_2_3');
$I->wait(0.5);
$I->executeJS('CKEDITOR.instances.sections_2_3_wysiwyg.setData(CKEDITOR.instances.sections_2_3_wysiwyg.getData().replace(/At vero eos et accusam/, "Test 12345678"))');
$I->executeJs('CKEDITOR.instances.amendmentReason_wysiwyg.setData("The follow-up amendment");');
$I->fillField('#initiatorPrimaryName', 'A new person');
$I->fillField('#initiatorEmail', 'test@example.org');

$I->submitForm('#amendmentEditForm', [], 'save');

$I->wantTo('edit it again, without the own change being lost');
$I->submitForm('#amendmentConfirmForm', [], 'modify');
$I->wait(0.5);
$I->seeElement('#section_holder_2_3.modified');
$I->dontSeeElement('#section_holder_2_1.modified');
$I->see('A small replacement', '#section_holder_2_1');
$I->dontSeeElement('#section_holder_2_1 .ice-ins');

$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');


$I->wantTo('see that the changes of the amended amendment are retained');
$I->gotoAmendment(true, 'Testing_proposed_changes-630', AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->see('Ä1', '.amendingAmendmentRow');
$I->see('Test 12345678', 'ins');
$I->see('A small replacement', 'ins');

