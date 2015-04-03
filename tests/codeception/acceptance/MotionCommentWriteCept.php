<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('Write a comment');
$I->gotoStdMotion(true);

if (method_exists($I, 'executeJS')) {
    $I->cantSee('Kommentar schreiben');
    $I->click('#section_3_1 .comment .shower');
    $I->see('Kommentar schreiben', '#section_3_1');
}
$I->fillField('#comment_3_1_name', 'My Name');
$I->fillField('#comment_3_1_email', 'test@example.org');
$I->fillField('#comment_3_1_text', 'Some Text');
$I->submitForm('#section_3_1 .commentForm', [], 'writeComment');

$I->see('My Name');

// @TODO More Testing