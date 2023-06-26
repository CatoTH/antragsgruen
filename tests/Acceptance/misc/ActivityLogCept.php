<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('go to the regular activity log');
$I->gotoConsultationHome();

$I->loginAsStdAdmin();

$I->click('#sidebar .activitylog a');
$I->see('Änderungsantrag Ä2 eingereicht');
$I->dontSee('Testadmin hat den Verfahrensvorschlag bearbeitet');
$I->see('Testuser hat den Änderungsantrag Ä3');
$I->dontSee('Testadmin hat den Verfahrensvorschlag zu Ä3 bearbeitet');

$I->gotoMotionList()->gotoMotionEdit(118);
$I->click('.sidebarActions .activity');
$I->see('Testuser hat den Antrag eingereicht');
$I->see('Testadmin hat den Verfahrensvorschlag bearbeitet');

$I->gotoMotionList()->gotoAmendmentEdit(281);
$I->click('.sidebarActions .activity');
$I->see('Testuser hat den Änderungsantrag Ä3');
$I->see('Testadmin hat den Verfahrensvorschlag zu Ä3 bearbeitet');
