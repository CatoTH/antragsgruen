<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('Write a comment, but forgot my name');
$I->gotoStdMotion(true);

if (method_exists($I, 'executeJS')) {
    $I->cantSee('Kommentar schreiben');
    $I->click('#section_3_1 .comment .shower');
}
$I->see('Kommentar schreiben', '#section_3_1');
$I->fillField('#comment_3_1_name', '');
$I->fillField('#comment_3_1_email', 'test@example.org');
$I->fillField('#comment_3_1_text', 'Some Text');
$I->submitForm('#section_3_1 .commentForm', [], 'writeComment');

$I->see('Bitte gib deinen Namen an');
$I->see('Kommentar schreiben', '#section_3_1');
$I->canSeeInField('#comment_3_1_name', '');
$I->canSeeInField('#comment_3_1_email', 'test@example.org');
$I->canSeeInField('#comment_3_1_text', 'Some Text');



$I->wantTo('Enter the missing data');
$I->fillField('#comment_3_1_name', 'My Name');
$I->submitForm('#section_3_1 .commentForm', [], 'writeComment');

$I->see('My Name', '#section_3_1 .motionComment');
$I->see('Some Text', '#section_3_1 .motionComment');
$I->cantSeeElement('#section_3_1 .motionComment .delLink');



$I->wantTo('Delete the comment');
$I->loginAsStdAdmin();
$I->gotoStdMotion();

if (method_exists($I, 'executeJS')) {
    $I->cantSee('Kommentar schreiben');
    $I->click('#section_3_1 .comment .shower');
}
$I->see('Kommentar schreiben', '#section_3_1');

$I->seeElement('#section_3_1 .motionComment .delLink');

$I->submitForm('#section_3_1 .motionComment .delLink', [], 'deleteComment');

if (method_exists($I, 'executeJS')) {
    $I->cantSee('Kommentar schreiben');
    $I->click('#section_3_1 .comment .shower');
}
$I->cantSee('My Name', '#section_3_1 .motionComment');
$I->cantSee('Some Text', '#section_3_1 .motionComment');
