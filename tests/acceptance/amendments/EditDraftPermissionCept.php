<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create a draft amendment logged out');
$createPage = $I->gotoConsultationHome()->gotoAmendmentCreatePage(3);
$createPage->fillInValidSampleData('Neuer Testantrag 1');
$createPage->saveForm();
$I->see(mb_strtoupper('Änderungsantrag bestätigen'), 'h1');
$I->gotoConsultationHome();

$I->wantTo('edit the draft');
\app\tests\_pages\AmendmentEditPage::openBy($I, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
    'motionSlug'       => '3',
    'amendmentId'      => AcceptanceTester::FIRST_FREE_AMENDMENT_ID
]);
$I->see('Änderungsantrag zu A3 stellen', 'h1');
$I->seeInField(['name' => 'sections[1]'], 'Neuer Testantrag 1');


$I->wantTo('create a draft amendment logged in');
$createPage = $I->gotoConsultationHome()->gotoAmendmentCreatePage(3);
$I->loginAsStdUser();
$createPage->fillInValidSampleData('Neuer Testantrag 2');
$createPage->saveForm();
$I->see(mb_strtoupper('Änderungsantrag bestätigen'), 'h1');
$I->gotoConsultationHome();

$I->wantTo('edit the draft');
\app\tests\_pages\AmendmentEditPage::openBy($I, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
    'motionSlug'       => '3',
    'amendmentId'      => AcceptanceTester::FIRST_FREE_AMENDMENT_ID + 1
]);
$I->see('Änderungsantrag zu A3 stellen', 'h1');
$I->seeInField(['name' => 'sections[1]'], 'Neuer Testantrag 2');


$I->wantTo('edit the draft logged out (should not work)');
$I->logout();
\app\tests\_pages\AmendmentEditPage::openBy($I, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
    'motionSlug'       => '3',
    'amendmentId'      => AcceptanceTester::FIRST_FREE_AMENDMENT_ID + 1
]);
$I->dontSee('Änderungsantrag zu A3 stellen', 'h1');
$I->dontSeeElement(['name' => 'sections[1]']);
