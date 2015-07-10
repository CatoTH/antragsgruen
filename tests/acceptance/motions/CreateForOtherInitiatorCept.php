<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check that this option is not available for normal users');
$scenario->incomplete('not implemented yet');

$I->gotoConsultationHome()->gotoMotionCreatePage();
$I->dontSeeElement('input[name=otherInitiator]');
//@TODO
