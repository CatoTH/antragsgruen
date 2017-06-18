<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('edit a motion');
$I->loginAndGotoMotionList()->gotoMotionEdit(2);
$I->dontSeeElementInDOM('#sections_1');
$I->dontSeeElement('#sections_2');
$I->dontSeeElement('.saveholder .checkAmendmentCollissions');
$I->seeElement('.saveholder .save');

$I->click('#motionTextEditCaller button');
$I->seeElementInDOM('#sections_2');
$I->seeElement('.saveholder .checkAmendmentCollissions');
$I->dontSeeElement('.saveholder .save');

$I->executeJS('$("#motionStatus").selectlist("selectByValue", "9");');
$I->see('Erledigt', '#motionStatus .selected-label');

$I->fillField('#motionTitle', 'Neuer Titel');
$I->fillField('#motionTitlePrefix', 'A2neu');
$I->fillField('#motionDateCreation', '01.01.2015 01:02');
$I->fillField('#motionDateResolution', '02.03.2015 04:05');
$I->fillField('#motionNoteInternal', 'Test 123');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData() + "<p>Test 123</p>");');

$I->wantTo('see no conflicts');
$I->dontSeeElement('.amendmentCollissionsHolder .alert-success');
$I->executeJS('$(".saveholder .checkAmendmentCollissions").click();');
$I->wait(2);
$I->seeElement('.amendmentCollissionsHolder .alert-success');
$I->dontSeeElement('.saveholder .checkAmendmentCollissions');
$I->seeElement('.saveholder .save');


$I->executeJS('$(".wysiwyg-textarea .texteditor").focus();');
$I->executeJS('$(".wysiwyg-textarea .texteditor").focus();'); // focus isn't actually triggered the first time; no idea why o_O
$I->seeElement('.saveholder .checkAmendmentCollissions');
$I->dontSeeElement('.saveholder .save');

$I->wantTo('see a conflict');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData().replace("Wui helfgod Wiesn", "Wui helfgod Wiesn1"));');
$I->executeJS('$(".saveholder .checkAmendmentCollissions").click();');
$I->wait(2);
$I->dontSeeElement('.amendmentCollissionsHolder .alert-success');
$I->seeElement('.amendmentCollissionsHolder .alert-danger');
$I->seeElement('#amendmentOverride_274_2_7');

$I->executeJS('CKEDITOR.instances.amendmentOverride_274_2_7.setData(CKEDITOR.instances.amendmentOverride_274_2_7.getData().replace("Bla ,", "Bla,"));');


// @TODO Change tags
$I->submitForm('#motionUpdateForm', [], 'save');

$I->wantTo('verify the changes are visible');
$I->click('.sidebarActions .view');
$I->see(mb_strtoupper('A2neu: Neuer Titel'));
$I->see('Test 123');
$I->see('02.03.2015');
$I->see('01.01.2015');
$I->see('Wui helfgod Wiesn1');


$I->wantTo('verify the changes are visible in the amendments');
$I->gotoAmendment(true, 2, 274);
$I->see('Wui helfgod Wiesn1Bla');
$I->gotoAmendment(true, 2, 3);
$I->dontSee('Wui helfgod Wiesn');
