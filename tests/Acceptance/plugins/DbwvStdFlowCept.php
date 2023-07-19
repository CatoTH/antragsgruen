<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBDataDbwv();


$I->gotoConsultationHome(true, 'std', 'lv-sued');
$I->loginAsDbwvTestUser('testadmin');
$I->see('Seiten-Admin', '#userLoginPanel');

