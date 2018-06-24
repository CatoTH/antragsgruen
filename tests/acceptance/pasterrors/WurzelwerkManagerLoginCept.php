<?php

/** @var \Codeception\Scenario $scenario */
$scenario->skip('Wurzelwerk not testable right now');

use app\tests\_pages\ManagerStartPage;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

ManagerStartPage::openBy($I);

$I->loginAsWurzelwerkUser();
