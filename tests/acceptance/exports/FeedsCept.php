<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the motion feed');
$I->gotoConsultationHome();
$I->click('#sidebar .feeds a');

$content = $I->downloadLink('.feedMotions');
if (mb_strpos($content, 'O’zapft is!') === false) {
    $I->fail('I don\'t see "O’zapft is!" in Source');
}
if (mb_strpos($content, 'Test') === false) {
    $I->fail('I don\'t see "Test" in Source');
}


$I->wantTo('test the amendment feed');
$I->gotoConsultationHome();
$I->click('#sidebar .feeds a');

$content = $I->downloadLink('.feedAmendments');
if (mb_strpos($content, 'Tester') === false) {
    $I->fail('I don\'t see "Tester" in Source');
}
if (mb_strpos($content, 'Ä1') === false) {
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
    if (mb_strpos($content, $look) === false) {
        $I->fail('I don\'t see "' . $look . '" in Source');
    }
}
