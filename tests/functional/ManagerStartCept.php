<?php

use app\tests\_pages\ManagerStartPage;

/** @var \Codeception\Scenario $scenario */

$I = new FunctionalTester($scenario);
$I->wantTo('ensure that ManagerStartPage works');
$I->amOnPage('http://antragsgruen-test.local/');
$I->see('das grüne Antragstool', 'h1');
