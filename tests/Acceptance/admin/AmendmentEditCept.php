<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\IMotion;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('edit an amendment');
$I->loginAndGotoMotionList()->gotoAmendmentEdit(1);
$I->see('Lorem ipsum dolor sit amet');
$I->see('Oamoi a Maß');
$I->see('Auf gehds beim Schichtl');
$I->dontSeeElement('#sections_2');
$I->click('#amendmentTextEditCaller button');
$I->seeElementInDOM('#sections_2');

$I->selectOption('#amendmentStatus', IMotion::STATUS_COMPLETED);
$I->fillField('#amendmentStatusString', 'völlig erschöpft');
$I->fillField('#amendmentTitlePrefix', 'Ä1neu');
$I->fillField('#amendmentDateCreation', '01.01.2015 01:02');
$I->fillField('#amendmentDateResolution', '02.03.2015 04:05');
$I->fillField('#amendmentNoteInternal', 'Test 123');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData() + "<p>Test 123</p>");');
$I->executeJS('CKEDITOR.instances.amendmentReason_wysiwyg.setData("<p>Another Reason</p>");');
$I->submitForm('#amendmentUpdateForm', [], 'save');

$I->wantTo('verify the changes are visible');
$I->click('.sidebarActions .view');
$I->see(mb_strtoupper('Ä1neu zu A2'));
$I->see('Erledigt (völlig erschöpft)');
$I->see('Test 123', 'p.inserted');
$I->see('Another Reason');
$I->see('02.03.2015');
$I->see('01.01.2015');

$I->wantTo('see the changes in the motion list');
$I->gotoMotionList();
$I->see('Ä1neu', '.amendment1');
$I->see('Erledigt (völlig erschöpft)', '.amendment1');
