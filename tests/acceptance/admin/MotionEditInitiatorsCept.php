<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('edit an initiator');
$page = $I->loginAndGotoStdAdminPage()->gotoMotionList()->gotoMotionEdit(58);
$I->see('E-Mail: testuser@example.org', '.supporterForm');
$I->fillField('#initiatorName', 'Another test user');
$I->fillField('#initiatorEmail', 'test2@example.org');
$I->submitForm('#motionUpdateForm', [], 'save');
$I->see('E-Mail: testuser@example.org', '.supporterForm');
$I->seeInField('#initiatorName', 'Another test user');
$I->seeInField('#initiatorEmail', 'test2@example.org');

$scenario->incomplete('test cases not implemented yet');
// General motion data
// More initiators
// Supporters
$I->see('E-Mail: testuser@example.org', '.supporterForm');
