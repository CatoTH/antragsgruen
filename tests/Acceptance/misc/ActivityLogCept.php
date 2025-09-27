<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('go to the regular activity log');
$I->gotoConsultationHome();

$I->loginAsStdAdmin();

$I->click('#sidebar .activitylog a');
$I->see('Änderungsantrag Ä2 veröffentlicht');
$I->dontSee('Testadmin hat den Verfahrensvorschlag (Version -) bearbeitet');
$I->see('Testuser hat den Änderungsantrag Ä3');

$I->gotoMotionList()->gotoMotionEdit(118);
$I->click('.sidebarActions .activity');
$I->see('Testuser hat den Antrag veröffentlicht');
$I->see('Testadmin hat den Verfahrensvorschlag (Version -) bearbeitet');

$I->gotoMotionList()->gotoAmendmentEdit(281);
$I->click('.sidebarActions .activity');
$I->see('Testuser hat den Änderungsantrag Ä3');
$I->see('Testadmin hat den Verfahrensvorschlag (Version -) bearbeitet');
