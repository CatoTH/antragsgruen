<?php

/** @var \Codeception\Scenario $scenario */
use app\tests\_pages\ManagerStartPage;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

ManagerStartPage::openBy($I);

$I->loginAsWurzelwerkUser();
