<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\IMotion;
use app\models\settings\Consultation;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->see('A4');
$I->see('Testantrag');

$I->wantTo('set the motion to draft state and make sure it\' not visible');
$page = $I->gotoMotionList()->gotoMotionEdit(58);
$I->selectOption('#motionStatus', IMotion::STATUS_DRAFT);
$page->saveForm();


foreach (Consultation::getStartLayouts() as $layoutId => $layoutTitle) {
    $page = $I->gotoStdAdminPage()->gotoAppearance();
    $I->selectOption('#startLayoutType', $layoutId);
    $page->saveForm();
    $I->gotoConsultationHome();
    $I->dontSee('A4');
    $I->dontSee('Testantrag');
}
