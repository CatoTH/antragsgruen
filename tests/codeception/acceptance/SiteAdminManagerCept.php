<?php

use tests\codeception\_pages\ConsultationHomePage;

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('Login in as an admin');
$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
