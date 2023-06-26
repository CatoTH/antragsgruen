<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('withdraw the motion I created before');
$I->gotoConsultationHome();
$I->loginAsStdUser();

$I->gotoMotion(true, 3);
$I->seeElement('section.amendments .amendment2');
$I->seeElement('.bookmarks .amendment2');
$I->gotoAmendment(true, 3, 2);

$I->click('.sidebarActions .withdraw a');
$I->see('Willst du diesen Änderungsantrag wirklich zurückziehen?');
$I->submitForm('.withdrawForm', [], 'withdraw');
$I->see('Der Änderungsantrag wurde zurückgezogen.');
$I->see('Zurückgezogen', '.motionDataTable .statusRow');
$I->dontSeeElement('.sidebarActions .withdraw a');
$I->gotoConsultationHome();
$I->seeElement('.amendmentRow2.withdrawn');

$I->gotoMotion(true, 3);
$I->seeElement('section.amendments .amendment2');
$I->dontSeeElement('.bookmarks .amendment2');
