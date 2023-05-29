<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('edit an initiator, try setting an invalid user');
$I->loginAndGotoMotionList('bdk', 'bdk')->gotoMotionEdit(4);
$I->see('E-Mail: testuser@example.org', '.supporterForm');
$I->fillField('#initiatorPrimaryName', 'Another test user');
$I->fillField('#initiatorOrga', 'KV Test');
$I->fillField('#initiatorEmail', 'test2@example.org');
$I->fillField('#initiatorPhone', '01234567');

$I->dontSeeElement('.initiatorSetUsername');
$I->clickJS('.initiatorCurrentUsername .btnEdit');
$I->wait(0.2);
$I->dontSeeElement('.initiatorCurrentUsername');
$I->seeElement('.initiatorSetUsername');
$I->fillField('#initiatorSetUsername', 'invalid@example.org');

$I->submitForm('#motionUpdateForm', [], 'save');

$I->see('Benutzer*in nicht gefunden', '.alert');
$I->see('E-Mail: testuser@example.org', '.supporterForm');


$I->wantTo('confirm the changes are saved, unassign the user');

$page = $I->gotoMotionList()->gotoMotionEdit(4);
$I->see('E-Mail: testuser@example.org', '.supporterForm');
$I->seeInField('#initiatorPrimaryName', 'Another test user');
$I->seeInField('#initiatorOrga', 'KV Test');
$I->seeInField('#initiatorEmail', 'test2@example.org');
$I->seeInField('#initiatorPhone', '01234567');

$I->clickJS('.initiatorCurrentUsername .btnEdit');
$I->wait(0.2);
$I->seeElement('.initiatorSetUsername');
$I->fillField('#initiatorSetUsername', '');

$I->submitForm('#motionUpdateForm', [], 'save');

$I->dontSee('E-Mail: testuser@example.org', '.supporterForm');


$I->wantTo('assign the user again');

$I->clickJS('.initiatorCurrentUsername .btnEdit');
$I->wait(0.2);
$I->seeElement('.initiatorSetUsername');
$I->fillField('#initiatorSetUsername', 'testuser@example.org');

$I->submitForm('#motionUpdateForm', [], 'save');

$I->see('E-Mail: testuser@example.org', '.supporterForm');
