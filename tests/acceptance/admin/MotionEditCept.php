<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('edit a motion');
$I->loginAndGotoStdAdminPage()->gotoMotionList()->gotoMotionEdit(2);
$I->dontSeeElementInDOM('#sections_1');
$I->dontSeeElement('#sections_2');
$I->click('#motionTextEditCaller button');
$I->seeElementInDOM('#sections_2');

$I->selectOption('#motionStatus', 'Erledigt');
$I->fillField('#motionTitle', 'Neuer Titel');
$I->fillField('#motionTitlePrefix', 'A2neu');
$I->fillField('#motionDateCreation', '01.01.2015 01:02');
$I->fillField('#motionDateResolution', '02.03.2015 04:05');
$I->fillField('#motionNoteInternal', 'Test 123');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData() + "<p>Test 123</p>");');
// @TODO Change tags
$I->submitForm('#motionUpdateForm', [], 'save');

$I->wantTo('verify the changes are visible');
$I->click('.sidebarActions .view');
$I->see(mb_strtoupper('A2neu: Neuer Titel'));
$I->see('Test 123');
$I->see('02.03.2015');
$I->see('01.01.2015');
