<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$scenario->incomplete('not implemented yet');

$I->loginAndGotoStdAdminPage();
