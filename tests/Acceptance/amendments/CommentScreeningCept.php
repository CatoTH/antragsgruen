<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('write a comment (logged out)');
$I->gotoConsultationHome()->gotoAmendmentView(1);



$I->wantTo('enable screening and force e-mails');
$I->loginAsStdAdmin();
$I->dontSeeElement('#adminTodo');
$I->gotoStdAdminPage()->gotoConsultation();
$I->dontSeeCheckboxIsChecked('#screeningComments');
$I->checkOption('#screeningComments');
$I->submitForm('#consultationSettingsForm', [], 'save');
$I->seeCheckboxIsChecked('#screeningComments');
$I->logout();



$I->wantTo('write a comment (with screening)');
$I->gotoConsultationHome()->gotoAmendmentView(1);

$I->see('Kommentar schreiben', 'section.comments');
$I->fillField('#comment_-1_-1_name', 'Mein Name 2');
$I->fillField('#comment_-1_-1_email', '');
$I->fillField('#comment_-1_-1_text', 'Noch ein zweiter Kommentar');
$I->submitForm('section.comments .commentForm', [], 'writeComment');

$I->dontSee('Mein Name 2', 'section.comments .motionComment');
$I->dontSee('Noch ein zweiter Kommentar', 'section.comments .motionComment');
$I->see('1 Kommentar wartet auf Freischaltung', 'section.comments');


$I->fillField('#comment_-1_-1_name', 'Mein Name 3');
$I->fillField('#comment_-1_-1_email', 'testuser@example.org');
$I->fillField('#comment_-1_-1_text', 'Noch ein dritter Kommentar');
$I->submitForm('section.comments .commentForm', [], 'writeComment');

$I->dontSee('Mein Name 3', 'section.comments .motionComment');
$I->dontSee('Noch ein dritter Kommentar', 'section.comments .motionComment');
$I->see('2 Kommentare warten auf Freischaltung', 'section.comments');



$I->wantTo('screen the comment');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();

$I->click('#adminTodo');
$I->seeElement('.adminTodo .amendmentCommentScreen' . (AcceptanceTester::FIRST_FREE_COMMENT_ID + 0));
$I->seeElement('.adminTodo .amendmentCommentScreen' . (AcceptanceTester::FIRST_FREE_COMMENT_ID + 1));
$I->click('.adminTodo .amendmentCommentScreen' . (AcceptanceTester::FIRST_FREE_COMMENT_ID + 1) . ' a');

$I->see('Mein Name 2', 'section.comments .motionComment');
$I->see('Noch ein zweiter Kommentar', 'section.comments .motionComment');
$I->see('2 Kommentare warten auf Freischaltung', 'section.comments');
$commId = (AcceptanceTester::FIRST_FREE_COMMENT_ID + 0);
$I->see('noch nicht freigeschaltet', '#comment' . $commId);
$I->submitForm('#comment' . $commId . ' form.screening', [], 'commentScreeningAccept');

$I->see('1 Kommentar wartet auf Freischaltung', 'section.comments');
$commId = (AcceptanceTester::FIRST_FREE_COMMENT_ID + 1);
$I->see('noch nicht freigeschaltet', '#comment' . $commId);
$I->submitForm('#comment' . $commId . ' form.screening', [], 'commentScreeningReject');

$I->dontSeeElement('.commentScreeningQueue');
$I->see('Noch ein zweiter Kommentar');
$I->dontSee('Noch ein dritter Kommentar');


$I->gotoConsultationHome();
$I->dontSeeElement('#adminTodo');
