<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('login using wurzelwerk faker');

$I->gotoConsultationHome();
$I->loginAsWurzelwerkUser();
