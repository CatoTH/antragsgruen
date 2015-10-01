<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);

$scenario->incomplete('feature not implemented yet');

$I->see('dummy');

$I->populateDBData1();
