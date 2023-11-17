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
