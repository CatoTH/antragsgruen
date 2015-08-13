<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$scenario->incomplete('test case not implemented yet');

$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoMotion(2);
