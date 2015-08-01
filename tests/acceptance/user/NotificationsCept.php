<?php

/** @var \Codeception\Scenario $scenario */
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

$I->dontSeeCheckboxIsChecked('.notiMotion');
$I->dontSeeCheckboxIsChecked('.notiAmendment');
$I->dontSeeCheckboxIsChecked('.notiComment');


$I->wantTo('change my settings');

$I->checkOption('.notiMotion');
$I->checkOption('.notiComment');
$I->submitForm('.notificationForm', [], 'save');


$I->seeCheckboxIsChecked('.notiMotion');
$I->dontSeeCheckboxIsChecked('.notiAmendment');
$I->seeCheckboxIsChecked('.notiComment');


$I->checkOption('.notiAmendment');
$I->uncheckOption('.notiComment');
$I->submitForm('.notificationForm', [], 'save');

$I->seeCheckboxIsChecked('.notiMotion');
$I->seeCheckboxIsChecked('.notiAmendment');
$I->dontSeeCheckboxIsChecked('.notiComment');


$I->uncheckOption('.notiMotion');
$I->uncheckOption('.notiAmendment');
$I->submitForm('.notificationForm', [], 'save');

$I->dontSeeCheckboxIsChecked('.notiMotion');
$I->dontSeeCheckboxIsChecked('.notiAmendment');
$I->dontSeeCheckboxIsChecked('.notiComment');
