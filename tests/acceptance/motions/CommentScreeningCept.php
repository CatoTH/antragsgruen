<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$scenario->incomplete('feature not implemented yet');

$I->wantTo('write a comment');
$I->gotoConsultationHome(true, 'bdk', 'bdk')->gotoMotionView(4);

$I->dontSee('Kommentar schreiben');
$I->click('#section_21_1 .comment .shower');
$I->see('Kommentar schreiben', '#section_21_1');
$I->fillField('#comment_21_1_name', 'My Name');
$I->fillField('#comment_21_1_email', 'test@example.org');
$I->fillField('#comment_21_1_text', 'Some Text');
$I->submitForm('#section_21_1 .commentForm', [], 'writeComment');

$I->see(mb_strtoupper('My Name'), '#section_21_1 .motionComment');
$I->see('Some Text', '#section_21_1 .motionComment');
$I->dontSee('#section_21_1 .motionComment .delLink');
