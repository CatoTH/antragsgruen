<?php

use tests\codeception\_pages\MotionCreatePage;

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('Motion Create site loads');
MotionCreatePage::openBy(
    $I,
    [
        'subdomain'        => 'TestSite',
        'consultationPath' => 'TestPath',
    ]
);
$I->see('ANTRAG STELLEN', 'h1');
