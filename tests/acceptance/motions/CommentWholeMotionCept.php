<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('write a comment, but forget my name');
$I->gotoConsultationHome()->gotoMotionView(2);

$I->see('Kommentar schreiben', 'section.comments');
$I->fillField('#comment_-1_-1_name', '');
$I->fillField('#comment_-1_-1_email', 'test@example.org');
$I->fillField('#comment_-1_-1_text', 'Some Text');
$I->submitForm('section.comments .commentForm', [], 'writeComment');

$I->see('Bitte gib deinen Namen an');
$I->see('Kommentar schreiben', 'section.comments');
$I->seeInField('#comment_-1_-1_name', '');
$I->seeInField('#comment_-1_-1_email', 'test@example.org');
$I->seeInField('#comment_-1_-1_text', 'Some Text');


$I->wantTo('enter the missing data');
$I->fillField('#comment_-1_-1_name', 'My Name');
$I->submitForm('section.comments .commentForm', [], 'writeComment');

$I->see(mb_strtoupper('My Name'), 'section.comments .motionComment');
$I->see('Some Text', 'section.comments .motionComment');
$I->dontSee('section.comments .motionComment .delLink');



$I->wantTo('see the comment on the sidebar and the feed');
$I->gotoConsultationHome();
$I->see('My Name', '#sidebar .comments');
$I->click('.feedComments');
$I->seeInPageSource('My Name');
$I->gotoConsultationHome();
$I->click('.feedAll');
$I->seeInPageSource('My Name');




$I->wantTo('delete the comment');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoConsultationHome()->gotoMotionView(2);

$I->see('Kommentar schreiben', 'section.comments');

$I->seeElement('section.comments .motionComment .delLink');

$I->submitForm('section.comments .motionComment .delLink', [], '');
$I->wait(1);
$I->see('Wirklich löschen', '.bootbox');
$I->click('.bootbox .btn-primary');

$I->dontSee('My Name', 'section.comments .motionComment');
$I->dontSee('Some Text', 'section.comments .motionComment');
