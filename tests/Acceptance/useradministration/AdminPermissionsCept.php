<?php

/** @var \Codeception\Scenario $scenario */

use Tests\_pages\AdminAdminConsultationsPage;
use Tests\_pages\AdminConsultationPage;
use Tests\_pages\AdminMotionListPage;
use Tests\_pages\AdminMotionTypePage;
use Tests\_pages\AdminTranslationPage;
use Tests\_pages\AdminUsersPage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->gotoConsultationHome();
$I->loginAsConsultationAdmin();
$I->gotoStdAdminPage();

$I->wantTo('see the consultation-specific admin pages');
$I->seeElement('#consultationLink');
$I->seeElement('#translationLink');
$I->seeElement('#contentPages');
$I->seeElement('.motionType1');
$I->seeElement('.siteUsers');
$I->dontSeeElement('.siteConsultationsLink');
$I->dontSeeElement('.siteUserList');
$I->dontSeeElement('.siteConfigLink');

$I->wantTo('access these pages');
$I->openPage(AdminConsultationPage::class, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->seeElement('#consultationSettingsForm');
$I->openPage(AdminMotionListPage::class, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->seeElement('.motionListForm');
$I->openPage(AdminTranslationPage::class, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->seeElement('#wordingBaseForm');
$I->openPage(AdminMotionTypePage::class, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag', 'motionTypeId' => 1]);
$I->seeElement('.adminTypeForm');
$I->openPage(AdminUsersPage::class, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->seeElement('.userAdminList');
$I->openPage(AdminAdminConsultationsPage::class, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->see('Kein Zugriff auf diese Seite');
$I->dontSeeElement('.consultationEditForm');



$I->wantTo('get permission by the more powerful admin');
$I->gotoConsultationHome();
$I->logout();


$I->wantTo('Assign the site admin role to consultationadmin');
$I->loginAndGotoStdAdminPage()->gotoUserAdministration();

$I->dontSeeElement('.editUserModal');
$I->clickJS('.user7 .btnEdit');
$I->wait(0.5);
$I->seeElement('.editUserModal');
$I->checkOption('.editUserModal .userGroup1');
$I->clickJS('.editUserModal .btnSave');
$I->wait(0.3);
$I->see('Seiten-Admin', '.user7');

$I->logout();

$I->gotoConsultationHome();
$I->loginAsConsultationAdmin();
$I->gotoStdAdminPage();



$I->wantTo('see the rest of the admin pages as well');
$I->seeElement('#consultationLink');
$I->seeElement('#translationLink');
$I->seeElement('#contentPages');
$I->seeElement('.motionType1');
$I->seeElement('.siteUsers');
$I->seeElement('.siteConsultationsLink');

$I->openPage(AdminUsersPage::class, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->dontSee('Kein Zugriff auf diese Seite');
$I->seeElement('.userAdminList');
$I->openPage(AdminAdminConsultationsPage::class, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->dontSee('Kein Zugriff auf diese Seite');
$I->seeElement('.consultationEditForm');



$I->wantTo('be made to an proposed procedure admin');
$I->gotoConsultationHome();
$I->logout();

$I->loginAndGotoStdAdminPage()->gotoUserAdministration();
$I->clickJS('.user7 .btnEdit');
$I->wait(0.5);
$I->seeElement('.editUserModal');
$I->clickJS('.editUserModal .userGroup3');
$I->clickJS('.editUserModal .userGroup1');
$I->clickJS('.editUserModal .userGroup2');
$I->clickJS('.editUserModal .btnSave');
$I->wait(0.3);
$I->see('Antragskommission', '.user7');

$I->logout();

$I->gotoConsultationHome();
$I->loginAsConsultationAdmin();

$I->seeElement('#motionListLink');
$I->dontSeeElement('#adminLink');
$I->gotoMotionList();
$I->dontSeeElement('.actionCol');
$I->seeElement('.proposalCol');



$I->wantTo('be resigned from being an admin');
$I->gotoConsultationHome();
$I->logout();


$I->loginAndGotoStdAdminPage()->gotoUserAdministration();
$I->see('consultationadmin@example.org');
$I->clickJS('.userAdminList .user7 .btnRemove');
$I->wait(0.5);
$I->seeBootboxDialog('Single-Consultation Admin wirklich aus der Liste entfernen?');
$I->acceptBootboxConfirm();
$I->wait(0.5);
$I->dontSee('consultationadmin@example.org');


$I->logout();
$I->gotoConsultationHome();
$I->loginAsConsultationAdmin();
$I->gotoStdAdminPage();
$I->see('Kein Zugriff auf diese Seite');
$I->dontSeeElement('.adminIndex');



$I->wantTo('be an admin like at the beginning');
$I->gotoConsultationHome();
$I->logout();

$I->loginAndGotoStdAdminPage()->gotoUserAdministration();

$I->clickJS('.addUsersOpener.email');
$I->fillField('#emailAddresses', 'consultationadmin@example.org');
$I->fillField('#names', 'ignored');
$I->submitForm('.addUsersByLogin.multiuser', [], 'addUsers');

$I->wait(0.5);
$I->clickJS('.user7 .btnEdit');
$I->wait(0.5);
$I->seeElement('.editUserModal');
$I->checkOption('.editUserModal .userGroup2');
$I->clickJS('.editUserModal .btnSave');

$I->wait(0.3);
$I->see('Veranstaltungs-Admin', '.user7');


$I->logout();
$I->gotoConsultationHome();
$I->loginAsConsultationAdmin();
$I->gotoStdAdminPage();
$I->seeElement('#consultationLink');
