<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
