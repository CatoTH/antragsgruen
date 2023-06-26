<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('see the motion as a regular / logged out user');
$I->gotoConsultationHome()->gotoMotionView(2);
$I->seeElement('.sidebarActions .amendmentCreate');
$I->seeElement('.sidebarActions .download');
$I->dontSeeElement('.sidebarActions .edit');
$I->dontSeeElement('.sidebarActions .mergeamendments');
$I->dontSeeElement('.sidebarActions .adminEdit');
$I->dontSeeElement('.sidebarActions .withdraw');
$I->seeElement('.sidebarActions .back');

$I->wantTo('see the motion as the user who initiated it');
$I->loginAsStdUser();
$I->gotoConsultationHome()->gotoMotionView(2);
$I->seeElement('.sidebarActions .amendmentCreate');
$I->seeElement('.sidebarActions .download');
$I->dontSeeElement('.sidebarActions .edit');
$I->seeElement('.sidebarActions .withdraw');
$I->dontSeeElement('.sidebarActions .mergeamendments');
$I->dontSeeElement('.sidebarActions .adminEdit');
$I->seeElement('.sidebarActions .back');

$I->wantTo('see the motion as an admin');
$I->logout();
$I->loginAsStdAdmin();
$I->gotoConsultationHome()->gotoMotionView(2);
$I->seeElement('.sidebarActions .amendmentCreate');
$I->seeElement('.sidebarActions .download');
$I->dontSeeElement('.sidebarActions .edit');
$I->dontSeeElement('.sidebarActions .withdraw');
$I->seeElement('.sidebarActions .mergeamendments');
$I->seeElement('.sidebarActions .adminEdit');
$I->seeElement('.sidebarActions .back');
