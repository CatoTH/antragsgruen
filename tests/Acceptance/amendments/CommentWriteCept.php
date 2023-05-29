<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

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

$I->wantTo('write a reply to this comment');
$I->dontSeeElement('.replyComment');
$I->dontSeeElement('#comment_-1_-1_' . AcceptanceTester::FIRST_FREE_COMMENT_ID . '_text');
$I->click('#comment' . AcceptanceTester::FIRST_FREE_COMMENT_ID . ' .replyButton');
$I->seeElement('.replyComment');
$I->seeElement('#comment_-1_-1_' . AcceptanceTester::FIRST_FREE_COMMENT_ID . '_text');
$I->fillField('#comment_-1_-1_' . AcceptanceTester::FIRST_FREE_COMMENT_ID . '_name', 'My Name 2');
$I->fillField('#comment_-1_-1_' . AcceptanceTester::FIRST_FREE_COMMENT_ID . '_email', 'reply@example.org');
$I->fillField('#comment_-1_-1_' . AcceptanceTester::FIRST_FREE_COMMENT_ID . '_text', 'This is a reply');
$I->submitForm('#comment_-1_-1_' . AcceptanceTester::FIRST_FREE_COMMENT_ID . '_form', [], 'writeComment');
$I->see(mb_strtoupper('My Name 2'), '.motionCommentReplies .motionComment');
$I->see('This is a reply', '.motionCommentReplies .motionComment');
$I->dontSeeElementInDOM('.motionComment .delLink');



$I->gotoConsultationHome();
$I->click('#sidebar .feeds a');
$I->click('.feedAll');
$I->seeInPageSource('My Name');


$I->wantTo('see the comment on the sidebar and the feed');
$I->gotoConsultationHome();
$I->see('My Name', '#sidebar .comments');
$I->click('#sidebar .feeds a');
$I->click(' .feedComments');
$I->seeInPageSource('My Name');


$I->wantTo('disable comments for this specific amendment');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();

$page = $I->gotoMotionList()->gotoAmendmentEdit(1);
$I->checkOption('.preventFunctionality .notCommentable input');
$page->saveForm();
$I->gotoAmendment();
$I->seeElement('.commentsDeactivatedHint');
$I->dontSeeElement('#comment_-1_-1_text');


$I->wantTo('delete the comment');
$I->seeElement('#commentsTitle');
$I->clickJS('section.comments #comment1 .delLink button');
$I->seeBootboxDialog('Wirklich löschen', '.bootbox');
$I->acceptBootboxConfirm();

$I->cantSee('My Name', 'section.comments .motionComment');
$I->cantSee('Some Text', 'section.comments .motionComment');
