<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the motion feed');
$I->gotoConsultationHome();
$I->click('#sidebar .feeds a');

$content = $I->downloadLink('.feedMotions');
if (!str_contains($content, 'O’zapft is!')) {
    $I->fail('I don\'t see "O’zapft is!" in Source');
}
if (!str_contains($content, 'Test')) {
    $I->fail('I don\'t see "Test" in Source');
}


$I->wantTo('test the amendment feed');
$I->gotoConsultationHome();
$I->click('#sidebar .feeds a');

$content = $I->downloadLink('.feedAmendments');
if (!str_contains($content, 'Tester')) {
    $I->fail('I don\'t see "Tester" in Source');
}
if (!str_contains($content, 'Ä1')) {
    $I->fail('I don\'t see "Ä1" in Source');
}


// The comment feed is tested in MotionCommentWriteCept and AmendmentCommentWriteCept


$I->wantTo('test the overall feed');
$I->gotoConsultationHome();
$I->click('#sidebar .feeds a');

$content = $I->downloadLink('.feedAll');
$lookFor = [
    'O’zapft is!',
    'Test',
    'Tester',
    'Ä1',
    'Oamoi a Maß',
    'Auf gehds beim Schichtl pfiad',
];
foreach ($lookFor as $look) {
    if (!str_contains($content, $look)) {
        $I->fail('I don\'t see "' . $look . '" in Source');
    }
}
