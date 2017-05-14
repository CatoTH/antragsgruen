<?php

/** @var \Codeception\Scenario $scenario */

use app\tests\_pages\AdminAdminConsultationsPage;
use app\tests\_pages\AdminConsultationPage;
use app\tests\_pages\AdminMotionListPage;
use app\tests\_pages\AdminMotionTypePage;
use app\tests\_pages\AdminSiteAccessPage;
use app\tests\_pages\AdminTranslationPage;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->gotoConsultationHome();
$I->loginAsConsultationAdmin();
$I->gotoStdAdminPage();

$I->wantTo('see the consultation-specific admin pages');
$I->seeElement('#consultationLink');
$I->seeElement('#translationLink');
$I->seeElement('#helpCreateLink');
$I->seeElement('.motionType1');
$I->dontSeeElement('.siteAccessLink');
$I->dontSeeElement('.siteConsultationsLink');
$I->dontSeeElement('.siteUserList');
$I->dontSeeElement('.siteConfigLink');

$I->wantTo('access these pages');
AdminConsultationPage::openBy($I, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->seeElement('#consultationSettingsForm');
AdminMotionListPage::openBy($I, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->seeElement('.motionListForm');
AdminTranslationPage::openBy($I, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->seeElement('#wordingBaseForm');
AdminMotionTypePage::openBy(
    $I,
    ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag', 'motionTypeId' => 1]
);
$I->seeElement('.adminTypeForm');
AdminSiteAccessPage::openBy($I, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->see('Kein Zugriff auf diese Seite');
$I->dontSeeElement('#siteSettingsForm');
AdminAdminConsultationsPage::openBy($I, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->see('Kein Zugriff auf diese Seite');
$I->dontSeeElement('.consultationEditForm');



$I->wantTo('get permission by the more powerful admin');
$I->gotoConsultationHome();
$I->logout();


$adminPage = $I->loginAndGotoStdAdminPage();
$accessPage = $adminPage->gotoSiteAccessPage();
$I->seeCheckboxIsChecked('.admin7 .type-con input');
$I->checkOption('.admin7 .type-site input');
$I->submitForm('#adminForm', [], 'saveAdmin');

$I->logout();

$I->gotoConsultationHome();
$I->loginAsConsultationAdmin();
$I->gotoStdAdminPage();



$I->wantTo('see therest of the admin pages as well');
$I->seeElement('#consultationLink');
$I->seeElement('#translationLink');
$I->seeElement('#helpCreateLink');
$I->seeElement('.motionType1');
$I->seeElement('.siteAccessLink');
$I->seeElement('.siteConsultationsLink');

AdminSiteAccessPage::openBy($I, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->dontSee('Kein Zugriff auf diese Seite');
$I->seeElement('#siteSettingsForm');
AdminAdminConsultationsPage::openBy($I, ['subdomain' => 'stdparteitag', 'consultationPath' => 'std-parteitag']);
$I->dontSee('Kein Zugriff auf diese Seite');
$I->seeElement('.consultationEditForm');




$I->wantTo('be resigned from being an admin');
$I->gotoConsultationHome();
$I->logout();


$adminPage = $I->loginAndGotoStdAdminPage();
$accessPage = $adminPage->gotoSiteAccessPage();
$I->see('consultationadmin@example.org');
$I->wait(1);
$I->click('.removeAdmin7');
$I->wait(1);
$I->seeBootboxDialog('Admin-Rechte entziehen');
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

$adminPage = $I->loginAndGotoStdAdminPage();
$accessPage = $adminPage->gotoSiteAccessPage();
$I->wait(1);
$I->dontSee('consultationadmin@example.org');
$I->executeJS('$("#adminAddForm .selectlist").selectlist("selectByValue", "email")');
$I->fillField('#addUsername', 'consultationadmin@example.org');
$I->submitForm('#adminAddForm', [], 'addAdmin');
$I->see('consultationadmin@example.org', '.admin7');

$I->logout();
$I->gotoConsultationHome();
$I->loginAsConsultationAdmin();
$I->gotoStdAdminPage();
$I->seeElement('#consultationLink');
