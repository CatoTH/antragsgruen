<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('write a comment, but forgot my name');
$I->gotoMotion(true);

$I->cantSee('Kommentar schreiben');
$I->click('#section_3_1 .comment .shower');
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


$I->wantTo('enter the missing data');
$I->fillField('#comment_3_1_name', 'My Name');
$I->submitForm('#section_3_1 .commentForm', [], 'writeComment');

$I->see('My Name', '#section_3_1 .motionComment');
$I->see('Some Text', '#section_3_1 .motionComment');
$I->cantSeeElement('#section_3_1 .motionComment .delLink');



$I->wantTo('see the comment on the sidebar and the feed');
$I->gotoStdConsultationHome();
$I->see('My Name', '#sidebar .comments');
$I->click('.feedComments');
$I->seeInPageSource('My Name');




$I->wantTo('delete the comment');
$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
$I->gotoMotion();

$I->cantSee('Kommentar schreiben');
$I->click('#section_3_1 .comment .shower');
$I->see('Kommentar schreiben', '#section_3_1');

$I->seeElement('#section_3_1 .motionComment .delLink');

$I->submitForm('#section_3_1 .motionComment .delLink', [], '');
$I->wait(1);
$I->see('Wirklich löschen', '.bootbox');
$I->click('.bootbox .btn-primary');

$I->cantSee('Kommentar schreiben');
$I->click('#section_3_1 .comment .shower');
$I->cantSee('My Name', '#section_3_1 .motionComment');
$I->cantSee('Some Text', '#section_3_1 .motionComment');
