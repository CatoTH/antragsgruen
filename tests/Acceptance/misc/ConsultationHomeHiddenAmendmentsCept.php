<?php

/** @var \Codeception\Scenario $scenario */

use app\models\settings\Consultation;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$page = $I->loginAsStdAdmin()->gotoStdAdminPage()->gotoAppearance();
$I->selectOption('#startLayoutType', Consultation::START_LAYOUT_AGENDA_HIDE_AMEND);
$page->saveForm();

$I->gotoConsultationHome();
$I->seeElement('.motionRow2 .amendmentsToggler.closed');
$I->dontSeeElement('.amendmentRow1');

$I->executeJS('$(".motionRow2 .amendmentsToggler button").click();');

$I->seeElement('.amendmentRow1');
