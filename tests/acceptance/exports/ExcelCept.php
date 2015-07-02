<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$scenario->skip('Not implemented yet');

$I->wantTo('test the single-motion-ODT from the admin interface');
$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
$I->click('#adminLink');
