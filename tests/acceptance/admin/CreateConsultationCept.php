<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$scenario->skip('test not implemented yet');

$I->loginAndGotoStdAdminPage();
