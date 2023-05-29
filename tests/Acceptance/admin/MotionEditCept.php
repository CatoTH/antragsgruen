<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\IMotion;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('edit a motion');
$I->loginAndGotoMotionList()->gotoMotionEdit(2);
$I->dontSeeElementInDOM('#sections_1');
$I->dontSeeElement('#sections_2');
$I->dontSeeElement('.saveholder .checkAmendmentCollisions');
$I->seeElement('.saveholder .save');

$I->click('#motionTextEditCaller button');
$I->seeElementInDOM('#sections_2');
$I->seeElement('.saveholder .checkAmendmentCollisions');
$I->dontSeeElement('.saveholder .save');

$I->selectOption('#motionStatus', IMotion::STATUS_COMPLETED);
$I->fillField('#motionStatusString', 'völlig erschöpft');

$I->fillField('#motionTitle', 'Neuer Titel');
$I->fillField('#motionTitlePrefix', 'A2neu');
$I->fillField('#motionDateCreation', '01.01.2015 01:02');
$I->fillField('#motionDateResolution', '02.03.2015 04:05');
$I->fillField('#motionNoteInternal', 'Test 123');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData() + "<p>Test 123</p>");');

$I->wantTo('see no conflicts');
$I->dontSeeElement('.amendmentCollisionsHolder .alert-success');
$I->executeJS('$(".saveholder .checkAmendmentCollisions").click();');
$I->wait(2);
$I->seeElement('.amendmentCollisionsHolder .alert-success');
$I->dontSeeElement('.saveholder .checkAmendmentCollisions');
$I->seeElement('.saveholder .save');


$I->executeJS('$(".wysiwyg-textarea .texteditor").focus();');
$I->executeJS('$(".wysiwyg-textarea .texteditor").focus();'); // focus isn't actually triggered the first time; no idea why o_O
$I->seeElement('.saveholder .checkAmendmentCollisions');
$I->dontSeeElement('.saveholder .save');

$I->wantTo('see a conflict');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData().replace("Wui helfgod Wiesn", "Wui helfgod Wiesn1"));');
$I->executeJS('$(".saveholder .checkAmendmentCollisions").click();');
$I->wait(2);
$I->dontSeeElement('.amendmentCollisionsHolder .alert-success');
$I->seeElement('.amendmentCollisionsHolder .alert-danger');
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
$I->see('Erledigt (völlig erschöpft)');
$I->see('Wui helfgod Wiesn1');


$I->wantTo('verify the changes are visible in the amendments');
$I->gotoAmendment(true, 2, 274);
$I->see('Wui helfgod Wiesn1Bla');
$I->gotoAmendment(true, 2, 3);
$I->dontSee('Wui helfgod Wiesn');


$I->wantTo('see the changes in the motion list');
$I->gotoMotionList();
$I->see('A2neu', '.motion2');
$I->see('Neuer Titel', '.motion2');
$I->see('Erledigt (völlig erschöpft)', '.motion2');
