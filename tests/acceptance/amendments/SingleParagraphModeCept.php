<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->wantTo('activate single paragraph mode');
$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->checkOption('#typeAmendSinglePara input[type=checkbox]');
$I->submitForm('.adminTypeForm', [], 'save');
$I->see('Gespeichert.');
$I->seeCheckboxIsChecked('#typeAmendSinglePara input[type=checkbox]');


$I->wantTo('create an amendment');
$I->gotoConsultationHome()->gotoMotionView(2);
$I->click('.amendmentCreate a');
$I->wait(1);

$I->seeElement('#amendmentReasonHolder .cke_editable');
$I->dontSeeElement('#section_holder_2');
$I->seeElement('#section_holder_2_0');
$I->dontSee('#section_holder_2_0 .cke_editable');
$I->seeElement('#section_holder_2_0.modifyable');
$I->seeElement('#section_holder_2_1.modifyable');


$I->wantTo('click a paragraph');
$I->click('#section_holder_2_0');
$I->wait(1);
$I->dontSeeElement('#section_holder_2_0.modifyable');
$I->seeElement('#section_holder_2_0.modified');
$I->dontSeeElement('#section_holder_2_1.modifyable');

$I->click('#section_holder_2_1');
$I->wait(1);
$I->dontSeeElement('#section_holder_2_0.modifyable');
$I->seeElement('#section_holder_2_0.modified');
$I->dontSeeElement('#section_holder_2_1.modifyable');
$I->dontSeeElement('#section_holder_2_1.modified');



$I->wantTo('change paragraphs');
$I->executeJS('CKEDITOR.instances.sections_2_0_wysiwyg.setData("<p>Test 123 ablabl</p>");');
$I->see('Test 123 ablabl');
$I->click('#section_holder_2_0 .modifiedActions .revert');
$I->dontSee('Test 123 ablabl');
$I->seeElement('#section_holder_2_0.modifyable');
$I->seeElement('#section_holder_2_1.modifyable');

$I->click('#section_holder_2_1');
$I->wait(1);
$I->dontSeeElement('#section_holder_2_0.modifyable');
$I->dontSeeElement('#section_holder_2_0.modified');
$I->dontSeeElement('#section_holder_2_1.modifyable');
$I->seeElement('#section_holder_2_1.modified');

$I->executeJS('CKEDITOR.instances.sections_2_1_wysiwyg.setData("<p>Test 456</p>");');
$I->see('Test 456');



$I->wantTo('submit the amendment');
$I->fillField('#initiatorPrimaryName', 'Mein Name');
$I->fillField('#initiatorEmail', 'test@example.org');
$I->submitForm('#amendmentEditForm', [], 'save');

$I->see('Auffi Gamsbart', '.deleted');
$I->see('Test 456');


$I->wantTo('correct the amendment');
$I->submitForm('#amendmentConfirmForm', [], 'modify');
$I->see('Test 456');

$I->dontSeeElement('#section_holder_2_0.modifyable');
$I->dontSeeElement('#section_holder_2_0.modified');
$I->dontSeeElement('#section_holder_2_1.modifyable');
$I->seeElement('#section_holder_2_1.modified');

$I->click('#section_holder_2_1 .modifiedActions .revert');
$I->dontSee('Test 456');
$I->seeElement('#section_holder_2_0.modifyable');
$I->seeElement('#section_holder_2_1.modifyable');

$I->click('#section_holder_2_0');
$I->wait(1);
$I->dontSeeElement('#section_holder_2_0.modifyable');
$I->seeElement('#section_holder_2_0.modified');
$I->dontSeeElement('#section_holder_2_1.modifyable');

$I->executeJS('CKEDITOR.instances.sections_2_0_wysiwyg.setData("<p>Test 789</p>");');
$I->see('Test 789');

$I->submitForm('#amendmentEditForm', [], 'save');
$I->dontSee('Auffi Gamsbart', '.deleted');
$I->dontSee('Test 456');
$I->see('Bavaria ipsum dolor');
$I->see('Test 789');

$I->submitForm('#amendmentConfirmForm', [], 'confirm');


$I->wantTo('check if the amendment is correctly displayed');

$I->gotoConsultationHome();
$I->click('.amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->see('Bavaria ipsum dolor', '.deleted');
$I->see('Test 789', '.inserted');
$I->dontSee('Test 456');
$I->dontSee('Test 123');



$I->wantTo('edit the amendment as admin');

$I->gotoMotionList()->gotoAmendmentEdit(AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->wait(1);
$I->dontSeeElement('#amendmentTextEditHolder');
$I->executeJS('$("#amendmentTextEditCaller button").click()');
$I->wait(1);
$I->seeElement('#amendmentTextEditHolder');

$I->see('Test 789', '#sections_2_0_wysiwyg');
$I->click('#section_holder_2_0 .modifiedActions .revert');
$I->dontSee('Test 789', '#sections_2_0_wysiwyg');
$I->seeElement('#section_holder_2_0.modifyable');
$I->seeElement('#section_holder_2_1.modifyable');

$I->click('#section_holder_2_1');
$I->wait(1);
$I->dontSeeElement('#section_holder_2_0.modifyable');
$I->dontSeeElement('#section_holder_2_0.modified');
$I->dontSeeElement('#section_holder_2_1.modifyable');
$I->seeElement('#section_holder_2_1.modified');

$I->executeJS('CKEDITOR.instances.sections_2_1_wysiwyg.setData("<p>Test 456</p>");');
$I->see('Test 456');

$I->submitForm('#amendmentUpdateForm', [], 'save');

$I->see('Gespeichert.');

$I->see('Test 456', '.motionTextHolder .inserted');
$I->see('Auffi Gamsbart nimma de Sepp', '.motionTextHolder .deleted');
