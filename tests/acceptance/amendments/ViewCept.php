<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('see the amendment as a regular / logged out user');
$I->gotoConsultationHome()->gotoAmendmentView(2);
$I->seeElement('.sidebarActions .download');
$I->dontSeeElement('.sidebarActions .edit');
$I->dontSeeElement('.sidebarActions .adminEdit');
$I->dontSeeElement('.sidebarActions .withdraw');
$I->seeElement('.sidebarActions .back');

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
