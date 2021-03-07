<?php

/** @var \Codeception\Scenario $scenario */
$scenario->skip('Grünes Netz not testable right now');

use app\tests\_pages\ManagerStartPage;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->openPage(ManagerStartPage::class);

$I->loginAsGruenesNetzUser();
