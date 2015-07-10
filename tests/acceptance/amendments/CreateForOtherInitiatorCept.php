<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check that this option is not available for normal users');
$scenario->incomplete('not implemented yet');

$I->gotoConsultationHome()->gotoMotionView(2);
$I->click('.amendmentCreate a');
$I->dontSeeElement('input[name=otherInitiator]');
//@TODO
