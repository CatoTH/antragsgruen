<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$scenario->incomplete('test case not implemented yet');

$I->wantTo('write a comment, but forget my name');
$I->gotoConsultationHome()->gotoMotionView(2);


// @TODO Writing comment
