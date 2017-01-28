<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('edit a motion');
$I->loginAndGotoMotionList('supporter', 'supporter')->gotoMotionEdit(116);
$I->wait(1);
$I->seeNumberOfElements('#motionSupporterHolder > ul > li', 0);
$I->executeJS('$(".supporterRowAdder").click()');
$I->executeJS('$(".supporterRowAdder").click()');
$I->seeNumberOfElements('#motionSupporterHolder > ul > li', 2);
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(0) .supporterName").val("My Name 1");');
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(0) .supporterOrga").val("My Orga 1");');
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(1) .supporterName").val("My Name 2");');
$I->submitForm('#motionUpdateForm', [], 'save');

$I->gotoStdAdminPage('supporter', 'supporter')->gotoMotionTypes(10);
$I->checkOption('#typeHasOrgaRow input[type=checkbox]');
$I->submitForm('.adminTypeForm', [], 'save');

$I->logout();


$I->wantTo('support the motion as a regular user');

\app\tests\_pages\MotionPage::openBy($I, [
    'subdomain'        => 'supporter',
    'consultationPath' => 'supporter',
    'motionSlug'       => 116,
]);
$I->loginAsStdUser();
$I->fillField('input[name=motionSupportName]', 'My login-name');
$I->fillField('input[name=motionSupportOrga]', 'My login-organisation');
$I->submitForm('.motionSupportForm', [], 'motionSupport');

$I->seeNumberOfElements('section.supporters ul li', 3);
$thirdName = $I->executeJS('return $("section.supporters ul li:nth(2)").text()');
$I->assertContains('My login-name', $thirdName);

$I->logout();


$I->wantTo('edit the motion again');
$I->loginAndGotoMotionList('supporter', 'supporter')->gotoMotionEdit(116);
$I->seeNumberOfElements('#motionSupporterHolder > ul > li', 3);
$I->seeInField('#motionSupporterHolder > ul > li .supporterName', 'My login-name');
$I->see('testuser@example.org', '#motionSupporterHolder > ul > li');
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(2)").prependTo("#motionSupporterHolder > ul")');
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(0) .supporterName").val("My login-name 2");');
$I->submitForm('#motionUpdateForm', [], 'save');



\app\tests\_pages\MotionPage::openBy($I, [
    'subdomain'        => 'supporter',
    'consultationPath' => 'supporter',
    'motionSlug'       => 116,
]);
$firstName = $I->executeJS('return $("section.supporters ul li:nth(0)").text()');
$I->assertContains('My login-name 2', $firstName);





$I->wantTo('add some suporters using full-text');

$I->gotoMotionList()->gotoMotionEdit(116);

$I->dontSeeElement('#fullTextHolder');
$I->click('.fullTextAdder a');
$I->seeElement('#fullTextHolder');

$I->fillField('#fullTextHolder textarea', 'Yet another name, KV München; Another Name 3');
$I->click('#fullTextHolder .fullTextAdd');

$I->submitForm('#motionUpdateForm', [], 'save');


\app\tests\_pages\MotionPage::openBy($I, [
    'subdomain'        => 'supporter',
    'consultationPath' => 'supporter',
    'motionSlug'       => 116,
]);

$fifthName = $I->executeJS('return $("section.supporters ul li:nth(4)").text()');
$I->assertContains('Another Name 3', $fifthName);
$fourthName = $I->executeJS('return $("section.supporters ul li:nth(3)").text()');
$I->assertContains('KV München', $fourthName);
