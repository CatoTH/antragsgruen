<?php

/** @var \Codeception\Scenario $scenario */

$I = new AcceptanceTester($scenario);
$I->populateDBDataYfj();

$I->gotoConsultationHome(true, 'std', 'yfj-test');

$I->wantTo('Create and open a Roll Call');
$I->loginAsStdAdmin();
$I->click('#votingsLink');
$I->see('No votings are open');
$I->click('#sidebar .admin a');
$I->wait(0.2);

$I->clickJS('.createRollCall');
$I->fillField('#roll_call_number', '1');
$I->fillField('#roll_call_name', 'Friday evening');
$I->seeCheckboxIsChecked('#roll_call_create_groups');
$I->submitForm('.createRollCallForm', [], NULL);
$I->wait(0.2);

$I->see('Roll Call 1 (Friday evening)', '#voting1');
$I->see('Simple majority (15 out of 29 users)', '#voting1');
$I->see('Voting IS set up as YFJ Roll Call', '#voting1');

$I->clickJS('#voting1 .btnOpen');
$I->wait(0.2);

$I->logout();

$I->wantTo('Participate as a full member');
$I->loginAsYfjUser('ingyo-full', 0);


