<?php

/** @var \Codeception\Scenario $scenario */
$scenario->skip('Grünes Netz not testable right now');

use Tests\_pages\ManagerStartPage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->openPage(ManagerStartPage::class);

$I->loginAsGruenesNetzUser();
