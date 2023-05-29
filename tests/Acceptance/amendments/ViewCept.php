<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('see the amendment as a regular / logged out user');
$I->gotoConsultationHome()->gotoAmendmentView(2);
$I->seeElement('.sidebarActions .download');
$I->dontSeeElement('.sidebarActions .edit');
$I->dontSeeElement('.sidebarActions .adminEdit');
$I->dontSeeElement('.sidebarActions .withdraw');
$I->seeElement('.sidebarActions .back');
$I->seeElement('.motionRow');

$I->see('Von Zeile 1 bis 2:');
$I->see('Und noch eine neue Zeile', 'ins');
$I->dontSee('Listenpunkt (kursiv)');

$I->wantTo('test the full motion text view');
$I->dontSeeElement('#section_2 .dropdown-menu .showFullText');
$I->clickJS('#section_2 .dropdown-toggle');
$I->wait(0.1);
$I->seeElement('#section_2 .dropdown-menu li.selected .showOnlyChanges');
$I->seeElement('#section_2 .dropdown-menu .showFullText');
$I->clickJS('#section_2 .dropdown-menu .showFullText');
$I->wait(0.1);
$I->dontSeeElement('#section_2 .dropdown-menu .showFullText');
$I->dontSee('Von Zeile 1 bis 2:');
$I->see('Und noch eine neue Zeile', 'ins');
$I->see('Listenpunkt (kursiv)');

$I->wantTo('switch back to regular view');
$I->clickJS('#section_2 .dropdown-toggle');
$I->wait(0.1);
$I->seeElement('#section_2 .dropdown-menu li.selected .showFullText');
$I->clickJS('#section_2 .dropdown-menu .showOnlyChanges');
$I->wait(0.1);
$I->see('Von Zeile 1 bis 2:');
$I->see('Und noch eine neue Zeile', 'ins');
$I->dontSee('Listenpunkt (kursiv)');


$I->wantTo('see the amendment as the user who initiated it');
$I->loginAsStdUser();
$I->gotoConsultationHome()->gotoAmendmentView(2);
$I->seeElement('.sidebarActions .download');
$I->dontSeeElement('.sidebarActions .edit');
$I->seeElement('.sidebarActions .withdraw');
$I->dontSeeElement('.sidebarActions .adminEdit');
$I->seeElement('.sidebarActions .back');

$I->wantTo('see the amendment as an admin');
$I->logout();
$I->loginAsStdAdmin();
$I->gotoConsultationHome()->gotoAmendmentView(2);
$I->seeElement('.sidebarActions .download');
$I->dontSeeElement('.sidebarActions .edit');
$I->dontSeeElement('.sidebarActions .withdraw');
$I->seeElement('.sidebarActions .adminEdit');
$I->seeElement('.sidebarActions .back');

$I->wantTo('allow users to edit their motions');
$I->gotoStdAdminPage()->gotoConsultation();
$I->checkOption('#iniatorsMayEdit');
$I->submitForm('#consultationSettingsForm', [], 'save');

$I->wantTo('check that I can edit the amendment now as the initiator');
$I->gotoConsultationHome();
$I->logout();
$I->loginAsStdUser();
$I->gotoAmendment(true, 3, 2);
$I->seeElement('.sidebarActions .download');
$I->seeElement('.sidebarActions .edit');
$I->seeElement('.sidebarActions .withdraw');
$I->dontSeeElement('.sidebarActions .adminEdit');
$I->seeElement('.sidebarActions .back');
