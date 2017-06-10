<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('write a comment, but forgot my name');
$I->gotoAmendment(true);

$I->see('Kommentar schreiben', 'section.comments');
$I->fillField('#comment_-1_-1_name', '');
$I->fillField('#comment_-1_-1_email', 'test@example.org');
$I->fillField('#comment_-1_-1_text', 'Some Text');
$I->executeJS('$("[required]").removeAttr("required");');
$I->submitForm('section.comments .commentForm', [], 'writeComment');

$I->see('Bitte gib deinen Namen an');
$I->see('Kommentar schreiben', 'section.comments');
$I->canSeeInField('#comment_-1_-1_name', '');
$I->canSeeInField('#comment_-1_-1_email', 'test@example.org');
$I->canSeeInField('#comment_-1_-1_text', 'Some Text');



$I->wantTo('enter the missing data');
$I->fillField('#comment_-1_-1_name', 'My Name');
$I->submitForm('section.comments .commentForm', [], 'writeComment');

$I->see('My Name', 'section.comments .motionComment');
$I->see('Some Text', 'section.comments .motionComment');
$I->cantSeeElementInDOM('section.comments .motionComment .delLink');
$I->gotoConsultationHome();
$I->click('#sidebar .feedAll');
$I->seeInPageSource('My Name');



$I->wantTo('see the comment on the sidebar and the feed');
$I->gotoConsultationHome();
$I->see('My Name', '#sidebar .comments');
$I->click('#sidebar .feedComments');
$I->seeInPageSource('My Name');




$I->wantTo('delete the comment');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoAmendment();


$I->see('Kommentar schreiben', 'section.comments');
$I->seeElementInDOM('section.comments .motionComment .delLink');
$I->submitForm('section.comments .motionComment .delLink', [], '');
$I->seeBootboxDialog('Wirklich löschen', '.bootbox');
$I->acceptBootboxConfirm();

$I->cantSee('My Name', 'section.comments .motionComment');
$I->cantSee('Some Text', 'section.comments .motionComment');
