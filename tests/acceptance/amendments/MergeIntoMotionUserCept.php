<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('ensure I cannot merge amendments now');
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->gotoAmendment(true, 2, 276);
$I->dontSee('In den Antrag übernehmen', '#sidebar');

$I->wantTo('enable merging for users in restricted mode');
$I->logout();
$I->loginAsStdAdmin();
$I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->seeCheckboxIsChecked('#initiatorsCanMerge0');
$I->checkOption('#initiatorsCanMerge1');
$I->submitForm('.adminTypeForm', [], 'save');
$I->seeCheckboxIsChecked('#initiatorsCanMerge1');

$I->wantTo('merge amendments with collissions');
$I->logout();
$I->gotoConsultationHome();
$I->loginAsStdUser();

$I->gotoAmendment(true, 2, 3);
$I->see('In den Antrag übernehmen', '#sidebar');
$I->click('#sidebar .mergeIntoMotion a');
$I->see('Kann nicht automatisch übernommen werden', 'h1');
$I->see('Ä6 zu A2', 'ul');
$I->dontSeeElement('#amendmentMergeForm');


$I->wantTo('merge amendments without collissions');
$I->gotoAmendment(true, 2, 276);
$I->see('In den Antrag übernehmen', '#sidebar');
$I->click('#sidebar .mergeIntoMotion a');
$I->seeElement('#amendmentMergeForm');

