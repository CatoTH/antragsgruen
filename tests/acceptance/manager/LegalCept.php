<?php

use app\tests\_pages\ManagerStartPage;

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('ensure that ManagerStartPage works');
ManagerStartPage::openBy($I);
$I->see('das Antragstool', 'h1');


$I->wantTo('go to the legal page');
$I->click('#legalLink');
$I->see('Impressum');

$scenario->incomplete('not finished yet');
$I->see('Bearbeiten');
