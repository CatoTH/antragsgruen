<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\IMotion;
use app\models\settings\Consultation;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);

$I->populateDBData1();


$I->wantTo('create a resolution');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->click('.motionLink2');
$I->click('.sidebarActions .mergeamendments a');
$I->click('.mergeAllRow .btn-primary');
$I->wait(0.5);
$I->see('Oamoi a Maß', '.ice-ins');
$I->submitForm('.motionMergeForm', [], 'save');
$I->click("//input[@name='newStatus'][@value='resolution_final']");
$I->seeElement('#newInitiator');
$I->seeElement('#dateResolution');
$I->fillField('#newInitiator', 'Mitgliedervollversammlung');
$I->fillField('#dateResolution', '23.04.2017');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->see('Der Antrag wurde überarbeitet');

$I->wantTo('See the resolution on the default home page');
$I->gotoConsultationHome();
$I->see('O’zapft is!', '.sectionResolutions .motionLink' . (AcceptanceTester::FIRST_FREE_MOTION_ID + 1));
$I->see('O’zapft is!', '.motionLink2');


foreach (Consultation::getStartLayouts() as $layoutId => $layoutTitle) {
    $I->wantTo('Test with motions as default view');
    $page = $I->gotoStdAdminPage()->gotoAppearance();
    $I->selectOption('#startLayoutType', $layoutId);
    $I->executeJS('$("#showResolutionsCombined").prop("checked", false).trigger("change")');
    $I->seeElement('.showResolutionsSeparateHolder');
    $I->executeJS('$("input[name=\"settings[showResolutionsSeparateMode]\"][value=\"1\"]").prop("checked", true)');
    $page->saveForm();
    $I->gotoConsultationHome();
    $I->seeElement('.motionLink2');
    $I->dontSeeElement('.motionLink' . (AcceptanceTester::FIRST_FREE_MOTION_ID + 1));
    $I->click('#sidebarResolutions');
    $I->see('Beschlüsse', 'h1');
    $I->dontSeeElement('.motionLink2');
    $I->seeElement('.motionLink' . (AcceptanceTester::FIRST_FREE_MOTION_ID + 1));

    $I->wantTo('Test with resolutions as default view');
    $page = $I->gotoStdAdminPage()->gotoAppearance();
    $I->selectOption('#startLayoutType', $layoutId);
    $I->executeJS('$("input[name=\"settings[showResolutionsSeparateMode]\"][value=\"2\"]").prop("checked", true)');
    $page->saveForm();
    $I->gotoConsultationHome();
    $I->see('Beschlüsse', '.green');
    $I->dontSeeElement('.motionLink2');
    $I->seeElement('.motionLink' . (AcceptanceTester::FIRST_FREE_MOTION_ID + 1));
    $I->click('#sidebarMotions');
    $I->see('Anträge', 'h1');
    $I->seeElement('.motionLink2');
    $I->dontSeeElement('.motionLink' . (AcceptanceTester::FIRST_FREE_MOTION_ID + 1));
}
