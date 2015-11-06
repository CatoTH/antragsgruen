<?php

use app\tests\_pages\ManagerStartPage;

/** @var \Codeception\Scenario $scenario */

$I = new FunctionalTester($scenario);
$I->wantTo('ensure that ManagerStartPage works');
ManagerStartPage::openBy($I);
$I->see('das grüne Antragstool', 'h1');
