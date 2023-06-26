<?php

/** @var \Codeception\Scenario $scenario */

use app\models\db\UserNotification;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('go to the notifications');
$I->gotoConsultationHome();
$I->click('#sidebar .notifications a');
$I->see('Login', 'h1');

$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->click('#sidebar .notifications a');
$I->see('Benachrichtigungen', 'h1');

$I->dontSeeCheckboxIsChecked('.notiMotion input');
$I->seeCheckboxIsChecked('.notiAmendment input');
$I->seeCheckboxIsChecked("//input[@name='notifications[amendmentsettings]'][@value='0']"); // Only to my motions
$I->dontSeeCheckboxIsChecked('.notiComment input');
$I->dontSeeElement('.commentSettings');

$I->wantTo('change my settings');

$I->checkOption('.notiMotion input');
$I->checkOption('.notiComment input');
$I->seeElement('.commentSettings');
$I->seeCheckboxIsChecked("//input[@name='notifications[commentsetting]'][@value='" . UserNotification::COMMENT_SAME_MOTIONS . "']");
$I->checkOption("//input[@name='notifications[commentsetting]'][@value='" . UserNotification::COMMENT_ALL_IN_CONSULTATION . "']");

$I->submitForm('.notificationForm', [], 'save');


$I->seeCheckboxIsChecked('.notiMotion input');
$I->seeCheckboxIsChecked('.notiComment input');
$I->seeElement('.commentSettings');
$I->seeCheckboxIsChecked("//input[@name='notifications[commentsetting]'][@value='" . UserNotification::COMMENT_ALL_IN_CONSULTATION . "']");


$I->uncheckOption('.notiAmendment input');
$I->uncheckOption('.notiComment input');
$I->submitForm('.notificationForm', [], 'save');

$I->seeCheckboxIsChecked('.notiMotion input');
$I->dontSeeCheckboxIsChecked('.notiAmendment input');
$I->dontSeeElement('.amendmentSettings');
$I->dontSeeCheckboxIsChecked('.notiComment input');
$I->dontSeeElement('.commentSettings');
