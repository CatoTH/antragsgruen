<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('edit an initiator');
$page = $I->loginAndGotoStdAdminPage()->gotoMotionList()->gotoAmendmentEdit(2);
$I->see('E-Mail: testuser@example.org', '.supporterForm');
$I->fillField('#initiatorName', 'Another test user');
$I->fillField('#initiatorEmail', 'test2@example.org');
$I->fillField('#resolutionDate', '23.05.1949');
$I->fillField('#initiatorOrga', 'Tester');
$I->submitForm('#amendmentUpdateForm', [], 'save');
$I->see('E-Mail: testuser@example.org', '.supporterForm');
$I->seeInField('#initiatorName', 'Another test user');
$I->seeInField('#initiatorEmail', 'test2@example.org');
$I->seeInField('#resolutionDate', '23.05.1949');
$I->seeInField('#initiatorOrga', 'Tester');

$scenario->incomplete('test cases not implemented yet');
// General amendment data
// More initiators
// Supporters
$I->see('E-Mail: testuser@example.org', '.supporterForm');
