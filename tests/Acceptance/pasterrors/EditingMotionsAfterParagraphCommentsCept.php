<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enable paragraph-based comments');
$form = $I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->checkOption('.section2 .commentParagraph input');
$form->saveForm();

$I->wantTo('write a comment');
$I->gotoConsultationHome()->gotoMotionView(2);
$I->wait(1);
$I->scrollTo('#section_2_5 .comment .shower');
$I->click('#section_2_5 .comment .shower');
$I->seeElement('#section_2_5 .commentForm');
$I->fillField('#comment_2_5_text', "My test'\n\\My test 2");
$I->submitForm('#section_2_5 .commentForm', [], 'writeComment');

$I->wantTo('see the comment');
$I->see('Testadmin', '#section_2_5 .motionComment');
$I->see("My test'\n\\My test 2", '#section_2_5 .motionComment');

$I->wantTo('edit the motion');
$I->click('#sidebar .adminEdit a');
$I->fillField('#motionTitle', 'My new title');
$I->submitForm('#motionUpdateForm', [], 'save');
$I->gotoConsultationHome();
$I->see('My new title');
