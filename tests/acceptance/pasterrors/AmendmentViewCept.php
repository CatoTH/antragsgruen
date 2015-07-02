<?php

use app\tests\_pages\AmendmentPage;

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
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
