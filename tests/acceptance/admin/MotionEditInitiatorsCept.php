<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('edit an initiator');
$I->loginAndGotoMotionList('bdk', 'bdk')->gotoMotionEdit(4);
$I->see('E-Mail: testuser@example.org', '.supporterForm');
$I->fillField('#initiatorPrimaryName', 'Another test user');
$I->fillField('#initiatorOrga', 'KV Test');
$I->fillField('#initiatorEmail', 'test2@example.org');
$I->fillField('#initiatorPhone', '01234567');

/*
$I->dontSeeElement('.initiatorData .initiatorRow');
$I->executeJS('$(".initiatorData .adderRow a").click();');
$I->seeElement('.initiatorData .initiatorRow');
$I->fillField('.initiatorData .initiatorRow .name', 'My Friend');
$I->fillField('.initiatorData .initiatorRow .organization', 'Her KV');
*/


$I->submitForm('#motionUpdateForm', [], 'save');

$I->wantTo('confirm the changes are saved');

$page = $I->gotoMotionList()->gotoMotionEdit(4);
$I->see('E-Mail: testuser@example.org', '.supporterForm');
$I->seeInField('#initiatorPrimaryName', 'Another test user');
$I->seeInField('#initiatorOrga', 'KV Test');
$I->seeInField('#initiatorEmail', 'test2@example.org');
$I->seeInField('#initiatorPhone', '01234567');

/*
$I->seeElement('.initiatorData .initiatorRow');
$I->seeInField('.initiatorData .initiatorRow .name', 'My Friend');
$I->seeInField('.initiatorData .initiatorRow .organization', 'Her KV');
*/

