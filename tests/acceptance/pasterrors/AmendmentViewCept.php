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
        'motionSlug'       => '2',
        'amendmentId'      => 1,
    ]
);
$I->see('Oamoi a Maß');



$page = AmendmentPage::openBy(
    $I,
    [
        'subdomain'        => 'stdparteitag',
        'consultationPath' => 'std-parteitag',
        'motionSlug'       => '3',
        'amendmentId'      => 2,
    ]
);
$I->see('Um das ganze mal zu testen');
$I->dontSee('###FORCELINEBREAK###');
