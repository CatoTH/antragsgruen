<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome()->gotoMotionView(2);

$I->wantTo('check that AE2 is working correctly');
$I->dontSee('Neuer Punkt');
$I->see('Auffi Gamsbart nimma');
$I->executeJS('$("#section_2_1").find("ul.bookmarks .amendment3").mouseover()');
$I->see('Neuer Punkt');
$I->see('Auffi Gamsbart nimma');
$I->executeJS('$("#section_2_1").find("ul.bookmarks .amendment3").mouseout()');
$I->dontSee('Neuer Punkt');
$I->see('Auffi Gamsbart nimma');

$I->see('Woibbadinga noch da Giasinga');
$I->dontSee('Woibbadinga noch da Giasinga', '.deleted');
$I->executeJS('$("#section_2_4").find("ul.bookmarks .amendment3").mouseover()');
$I->see('Woibbadinga noch da Giasinga', '.deleted');
$I->executeJS('$("#section_2_4").find("ul.bookmarks .amendment3").mouseout()');
$I->dontSee('Woibbadinga noch da Giasinga', '.deleted');
