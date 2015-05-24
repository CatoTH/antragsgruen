<?php

use tests\codeception\_pages\AmendmentPage;

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$page = AmendmentPage::openBy(
    $I,
    [
        'subdomain'        => 'stdparteitag',
        'consultationPath' => 'std-parteitag',
        'motionId'         => 2,
        'amendmentId'      => 1,
    ]
);
$I->see('Oamoi a Maß');
