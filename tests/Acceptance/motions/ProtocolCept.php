<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('set a non-public protocol');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$form = $I->gotoMotionList()->gotoMotionEdit(2);

$I->dontSeeElement('.protocolHolder');
$I->clickJS('.contentProtocolCaller button');
$I->wait(0.2);
$I->seeElement('.protocolHolder');
$I->executeJS('CKEDITOR.instances.protocol_text_wysiwyg.setData("<p>Famous quote</p><blockquote>So Long, and Thanks for All the Fish</blockquote>")');
$form->saveForm();

$I->gotoMotion();
$I->dontSeeElement('.motionProtocol .protocolOpener');


$I->wantTo('make the protocol public');
$I->gotoMotionList()->gotoMotionEdit(2);
$I->checkOption("//input[@name='protocol_public'][@value='1']");
$form->saveForm();

$I->gotoMotion();
$I->clickJS('.motionProtocol .protocolOpener');
$I->see('So Long, and Thanks for All the Fish', '.protocolHolder');
