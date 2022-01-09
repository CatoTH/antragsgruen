<?php

/** @var \Codeception\Scenario $scenario */

use app\tests\_pages\{AdminAdminConsultationsPage,
    AdminConsultationPage,
    AdminMotionListPage,
    AdminMotionTypePage,
    AdminSiteAccessPage,
    AdminTranslationPage,
    AdminUsersPage};

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
$I->openPage(AdminSiteAccessPage::class, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->see('Kein Zugriff auf diese Seite');
$I->dontSeeElement('#siteSettingsForm');
$I->openPage(AdminAdminConsultationsPage::class, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->see('Kein Zugriff auf diese Seite');
$I->dontSeeElement('.consultationEditForm');



$I->wantTo('get permission by the more powerful admin');
$I->gotoConsultationHome();
$I->logout();


$I->wantTo('Assign the site admin role to consultationadmin');
$I->loginAndGotoStdAdminPage()->gotoUserAdministration();
$I->wait(1);
$I->dontSeeElement('.vs__dropdown-toggle');
$I->clickJS('.user7 .btnEdit');
$I->seeElement('.vs__dropdown-toggle');
$I->executeJS('userWidget.$refs["user-admin-widget"].setSelectedGroups([1], { id: 7 });');
$I->executeJS('userWidget.$refs["user-admin-widget"].saveUser({id: 7});');
$I->wait(0.5);
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
$I->wait(1);
$I->dontSeeElement('.vs__dropdown-toggle');
$I->clickJS('.user7 .btnEdit');
$I->seeElement('.vs__dropdown-toggle');
$I->executeJS('userWidget.$refs["user-admin-widget"].setSelectedGroups([3], { id: 7 });');
$I->executeJS('userWidget.$refs["user-admin-widget"].saveUser({id: 7});');
$I->wait(0.5);
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

$I->wait(1);
$I->clickJS('.userAdminList .user7 .btnRemove');
$I->wait(1);
$I->seeBootboxDialog('Single-Consultation Admin wirklich aus der Liste entfernen?');
$I->acceptBootboxConfirm();
$I->wait(1);
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
$I->submitForm('#accountsCreateForm', [], 'addUsers');

$I->wait(1);
$I->clickJS('.user7 .btnEdit');
$I->seeElement('.vs__dropdown-toggle');
$I->executeJS('userWidget.$refs["user-admin-widget"].setSelectedGroups([2], { id: 7 });');
$I->executeJS('userWidget.$refs["user-admin-widget"].saveUser({id: 7});');
$I->wait(0.5);
$I->see('Veranstaltungs-Admin', '.user7');


$I->logout();
$I->gotoConsultationHome();
$I->loginAsConsultationAdmin();
$I->gotoStdAdminPage();
$I->seeElement('#consultationLink');
