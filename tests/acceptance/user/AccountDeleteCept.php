<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$scenario->incomplete('feature not implemented yet');

$I->gotoConsultationHome();
$I->loginAsStdUser();
