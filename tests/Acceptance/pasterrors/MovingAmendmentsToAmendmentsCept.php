<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enable amendments to amendments');
$I->gotoConsultationHome();
$page = $I->loginAsStdAdmin()->gotoStdAdminPage()->gotoMotionTypes(1);
$I->checkOption('#allowAmendmentsToAmendments');
$page->saveForm();

$I->wantTo('create a new consultation');
$I->gotoStdAdminPage();
$I->click('.siteConsultationsLink');
$I->fillField('#newTitle', 'Test3');
$I->fillField('#newShort', 'test3');
$I->fillField('#newPath', 'test3');
$I->submitForm('.consultationCreateForm', [], 'createConsultation');


$I->wantTo('create an amendment based on another amendment');
$I->gotoConsultationHome()->gotoAmendmentView(278);
$I->click('#sidebar .amendmentCreate a');
$I->wait(0.5);
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData().replace(/Weit hinten/, "Weit hinten 123"))');
$I->executeJS('CKEDITOR.instances.sections_4_wysiwyg.setData("<p>Insert</p>")');
$I->fillField('#initiatorPrimaryName', 'Name');
$I->fillField('#initiatorEmail', 'test@example.org');
$I->submitForm('#amendmentEditForm', [], 'save');

$I->see('123', 'ins');
$I->see('Insert', '.inserted');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');

$I->submitForm('#motionConfirmedForm', [], '');
$I->see('Ä2', '#section_4');


$I->wantTo('move the motion to the second consultation');
$I->click('#sidebar .adminEdit a');
$I->click('#sidebar .move');
$I->checkOption("//input[@name='operation'][@value='copynoref']");
$I->checkOption("//input[@name='target'][@value='consultation']");
$I->seeElement('.moveToConsultationItem');
$I->submitForm('.adminMoveForm', [], 'move');
$I->click('.alert-success a');
$I->see('Test3', '.breadcrumb');
$I->gotoConsultationHome(true, 'stdparteitag', 'test3');


$I->wantTo('make sure that the new amendments are linked with each other and the old motion still works');
$I->seeElement('.motionRow' . AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->click('.motionRow' . AcceptanceTester::FIRST_FREE_MOTION_ID . ' .motionLink' . AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->click('.amendment' . (AcceptanceTester::FIRST_FREE_AMENDMENT_ID + 2) . ' a');
$I->see('123', 'ins');
$I->click('.amendingAmendmentRow a');
$I->see('Test3', '.breadcrumb');

$I->gotoMotion(true, 117);
$I->see('Test2', '.breadcrumb');
$I->click('.amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID . ' a');
$I->see('123', 'ins');
$I->see('Test2', '.breadcrumb');
