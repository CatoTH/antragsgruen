<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->dontSeeElement('#forceMotionRow');
$I->dontSeeCheckboxIsChecked('#singleMotionMode');

$I->wantTo('enable single-motion-mode');
$I->checkOption('#singleMotionMode');
$I->seeElement('#forceMotionRow');
$I->selectFueluxOption('#forceMotion', 3);
$I->submitForm('#consultationSettingsForm', [], 'save');

$I->seeElement('#forceMotionRow');
$I->seeCheckboxIsChecked('#singleMotionMode');


$I->wantTo('check that the setting is active');
$I->click('.homeLinkLogo');
$I->see('A3: Textformatierungen', 'h1');

$I->logout();
$I->click('.breadcrumb a');
$I->see('A3: Textformatierungen', 'h1');


$I->wantTo('unpublish the motion');
$I->loginAsStdAdmin();
$I->click('#sidebar .adminEdit a');
$I->executeJS('$("#motionStatus").selectlist("selectByValue", "' . \app\models\db\Motion::STATUS_DRAFT . '");');
$I->submitForm('#motionUpdateForm', [], 'save');


$I->click('.homeLinkLogo');
$I->see('A3: Textformatierungen', 'h1');
$I->seeElement('.alertDraft');
$I->seeElement('#sidebar .adminEdit');

$I->logout();
$I->dontSee('A3: Textformatierungen', 'h1');
$I->see('Dies ist kein regulär anzeigbarer Antrag.');
$I->dontSeeElement('#sidebar li');


$I->wantTo('overhaul the motion');
$I->loginAsStdAdmin();
$I->click('#sidebar .adminEdit a');
$I->executeJS('$("#motionStatus").selectlist("selectByValue", "' . \app\models\db\Motion::STATUS_SUBMITTED_SCREENED . '");');
$I->submitForm('#motionUpdateForm', [], 'save');


$I->click('.homeLinkLogo');
$I->see('A3: Textformatierungen', 'h1');
$I->click('#sidebar .mergeamendments a');
$I->submitForm('.mergeAllRow', []);
$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->submitForm('.motionMergeForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');

$I->click('.homeLinkLogo');
$I->see('A3neu: Textformatierungen', 'h1');
