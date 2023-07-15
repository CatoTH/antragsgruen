<?php

/** @var \Codeception\Scenario $scenario */
use app\models\policies\All;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('allow comments for everyone');
$I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->loginAsStdAdmin();
$I->gotoStdAdminPage('bdk', 'bdk')->gotoMotionTypes(7);
$I->selectOption('#typePolicyComments', All::getPolicyID());
$I->submitForm('.adminTypeForm', [], 'save');
$I->logout();


$I->wantTo('write a comment, but forget my name');
$I->gotoConsultationHome(true, 'bdk', 'bdk')->gotoMotionView(4);
$I->wait(0.5);

$I->dontSee('Kommentar schreiben');
$I->click('#section_21_1 .comment .shower');
$I->see('Kommentar schreiben', '#section_21_1');
$I->executeJS('$("#comment_21_1_name").removeAttr("required");');
$I->fillField('#comment_21_1_name', '');
$I->fillField('#comment_21_1_email', 'test@example.org');
$I->fillField('#comment_21_1_text', 'Some Text');
$I->submitForm('#section_21_1 .commentForm', [], 'writeComment');

$I->see('Bitte gib deinen Namen an');
$I->see('Kommentar schreiben', '#section_21_1');
$I->seeInField('#comment_21_1_name', '');
$I->seeInField('#comment_21_1_email', 'test@example.org');
$I->seeInField('#comment_21_1_text', 'Some Text');


$I->wantTo('enter the missing data');
$I->fillField('#comment_21_1_name', 'My Name');
$I->submitForm('#section_21_1 .commentForm', [], 'writeComment');

$I->see(mb_strtoupper('My Name'), '#section_21_1 .motionComment');
$I->see('Some Text', '#section_21_1 .motionComment');
$I->dontSeeElementInDOM('#section_21_1 .motionComment .delLink');


$I->wantTo('write a reply to this comment');
$I->dontSeeElement('.replyComment');
$I->dontSeeElement('#comment_21_1_' . AcceptanceTester::FIRST_FREE_COMMENT_ID . '_text');
$I->click('#comment' . AcceptanceTester::FIRST_FREE_COMMENT_ID . ' .replyButton');
$I->seeElement('.replyComment');
$I->seeElement('#comment_21_1_' . AcceptanceTester::FIRST_FREE_COMMENT_ID . '_text');
$I->fillField('#comment_21_1_' . AcceptanceTester::FIRST_FREE_COMMENT_ID . '_name', 'My Name 2');
$I->fillField('#comment_21_1_' . AcceptanceTester::FIRST_FREE_COMMENT_ID . '_email', 'reply@example.org');
$I->fillField('#comment_21_1_' . AcceptanceTester::FIRST_FREE_COMMENT_ID . '_text', 'This is a reply');
$I->submitForm('#comment_21_1_' . AcceptanceTester::FIRST_FREE_COMMENT_ID . '_form', [], 'writeComment');
$I->see(mb_strtoupper('My Name 2'), '#section_21_1 .motionCommentReplies .motionComment');
$I->see('This is a reply', '#section_21_1 .motionCommentReplies .motionComment');
$I->dontSeeElementInDOM('#section_21_1 .motionComment .delLink');


$I->wantTo('see the comment on the sidebar and the feed');
$I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->see('My Name', '#sidebar .comments');
$I->click('#sidebar .feeds a');
$I->click('.feedComments');
$I->seeInPageSource('My Name');
$I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->click('#sidebar .feeds a');
$I->click('.feedAll');
$I->seeInPageSource('My Name');




$I->wantTo('delete the comment');
$I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->loginAsStdAdmin();
$I->gotoConsultationHome(true, 'bdk', 'bdk')->gotoMotionView(4);
$I->wait(0.5);

$I->dontSee('Kommentar schreiben');
$I->click('#section_21_1 .comment .shower');
$I->see('Kommentar schreiben', '#section_21_1');

$I->seeElementInDOM('#section_21_1 .motionComment .delLink');

$I->executeJS('$("#section_21_1 #comment1 .delLink button").trigger("click");');
$I->wait(0.5);
$I->seeBootboxDialog('Wirklich löschen');
$I->acceptBootboxConfirm();

$I->dontSee('Kommentar schreiben');
$I->click('#section_21_1 .comment .shower');
$I->dontSee('My Name', '#section_21_1 .motionComment');
$I->dontSee('Some Text', '#section_21_1 .motionComment');


// @TODO Switching to section-based comments afterwards -> should be still visible
