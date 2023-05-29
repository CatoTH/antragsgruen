<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

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

$I->wantTo('merge amendments with collisions');
$I->logout();
$I->gotoConsultationHome();
$I->loginAsStdUser();

$I->gotoMotion(true, 2);
$I->see('Ä2', '.bookmarks');
$I->see('Ä7', '.bookmarks');
$I->see('Biawambn gscheid: Griasd', 'p');

$I->gotoAmendment(true, 2, 3);
$I->see('In den Antrag übernehmen', '#sidebar');
$I->click('#sidebar .mergeIntoMotion a');
$I->see('Kann nicht automatisch übernommen werden', 'h1');
$I->see('Ä6 zu A2', 'ul');
$I->dontSeeElement('#amendmentMergeForm');


$I->wantTo('merge amendments without collisions');
$I->gotoAmendment(true, 2, 276);
$I->see('In den Antrag übernehmen', '#sidebar');
$I->click('#sidebar .mergeIntoMotion a');
$I->seeElement('#amendmentMergeForm');
$I->fillField('#motionTitlePrefix', 'A2new');
$I->submitForm('#amendmentMergeForm', [], 'save');
$I->see('Der Änderungsantrag wurde eingepflegt.');
$I->click('.btn-primary');

$I->wantTo('see the new motion');
$I->see('A2new', 'h1');
$I->see('Ä2', '.bookmarks');
$I->dontSee('Ä7', '.bookmarks');
$I->dontSee('Biawambn gscheid: Griasd', 'p');
$I->see('Biawambn gscheid:', 'p');
$I->see('Griasd eich midnand', 'p');

$I->wantTo('see the old motion');
$I->clickJS('.motionDataTable .btnHistoryOpener');
$I->see('Version 2', '.motionHistory');
$I->click('.motionHistory a.motion2');
$I->see('A2:', 'h1');
$I->dontSee('Ä2', '.bookmarks');
$I->see('Ä7', '.bookmarks');
