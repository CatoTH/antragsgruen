<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\IMotion;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('ensure I cannot merge amendments now');
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->gotoAmendment(true, 2, 274);
$I->dontSee('In den Antrag übernehmen', '#sidebar');

$I->wantTo('enable merging for users in godlike mode');
$I->logout();
$I->loginAsStdAdmin();
$I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->seeCheckboxIsChecked('#initiatorsCanMerge0');
$I->checkOption('#initiatorsCanMerge2');
$I->submitForm('.adminTypeForm', [], 'save');
$I->seeCheckboxIsChecked('#initiatorsCanMerge2');

$I->wantTo('merge amendments with collisions');
$I->logout();
$I->gotoConsultationHome();
$I->loginAsStdUser();

$I->gotoMotion(true, 2);
$I->see('Ä2', '.bookmarks');
$I->see('Ä7', '.bookmarks');
$I->see('Wui helfgod Wiesn');
$I->dontSee('Alternatives Ende');

$I->gotoAmendment(true, 2, 274);
$I->see('Wui helfgod Wiesn', 'del');
$I->see('Alternatives Ende', 'ins');
$I->see('In den Antrag übernehmen', '#sidebar');
$I->click('#sidebar .mergeIntoMotion a');
$I->dontSee('Kann nicht automatisch übernommen werden', 'h1');
$I->cantSeeElementInDOM('.otherAmendmentStatus');
$I->selectOption('#amendmentStatus', IMotion::STATUS_MODIFIED_ACCEPTED);
$I->executeJS('$(".save-row .goto_2").click();');
$I->wait(1);
$I->click('.checkAmendmentCollisions');
$I->wait(2);
$I->see('Wui helfgod Wiesn', 'del');
$I->see('Woibbadinga damischa owe gwihss Sauwedda', 'ins');
$I->executeJS('CKEDITOR.instances.amendmentOverride_3_2_7.setData(CKEDITOR.instances.amendmentOverride_3_2_7.getData() + \'<p>Alternative ending</p>\');');
$I->submitForm('#amendmentMergeForm', [], 'save');
$I->see('Der Änderungsantrag wurde eingepflegt.', '.alert-success');

$I->wantTo('check the changes were made');
$I->click('.alert-success .btn-primary');
$I->see('A2', 'h1');
$I->see('Version 2', '.motionHistory');
$I->dontSee('Wui helfgod Wiesn');
$I->see('Alternatives Ende');

$I->wantTo('see the overridden amendment changes');
$I->gotoAmendment(true, AcceptanceTester::FIRST_FREE_MOTION_ID, 3);
$I->see('Alternatives Ende', 'del');
$I->see('Xaver Prosd eana an a bravs', 'ins');
$I->see('Alternative ending', 'p.inserted');
