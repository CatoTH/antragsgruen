<?php

/** @var \Codeception\Scenario $scenario */

use app\models\settings\Consultation;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$page = $I->loginAsStdAdmin()->gotoStdAdminPage()->gotoAppearance();
$I->selectOption('#startLayoutType', Consultation::START_LAYOUT_DISCUSSION_TAGS);
$page->saveForm();

$I->wantTo('test the tag filtering');

$I->gotoConsultationHome();
$I->see('Umwelt (5)', '.tagList .tag1');
$I->see('Listen-Test', '.motionLink115'); // Umwelt
$I->see('Testantrag', '.motionLink58'); // Verkehr
$I->dontSeeElement('.expandableRecentComments');
$I->dontSeeElement('.motionRow2 .comments');

$I->executeJS('$(".tagList .tag1").trigger("click")');
usleep(500000);
$I->dontSee('Testantrag', '.motionLink58'); // Verkehr
$I->see('Listen-Test', '.motionLink115'); // Umwelt

$I->wantTo('write a comment');

$I->gotoMotion();
$I->fillField('#comment_-1_-1_text', 'Test-Kommentar');
$I->submitForm('#comment_-1_-1_form', [], 'writeComment');

$I->see('Test-Kommentar', '#comment1');
$I->gotoConsultationHome();
$I->seeElement('.expandableRecentComments ');
$I->see('Test-Kommentar', '.motionComment');
$I->seeElement('.motionRow2 .comments');
