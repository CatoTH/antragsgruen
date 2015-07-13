<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('withdraw the motion I created before');
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->gotoAmendment(true, 3, 2);

$scenario->incomplete('feature not yet implemented');

$I->click('.sidebarActions .withdraw a');
