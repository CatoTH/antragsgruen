<?php

/** @var \Codeception\Scenario $scenario */
use app\models\sectionTypes\ISectionType;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Create a motion type with hidden section');
$I->loginAndGotoStdAdminPage();
$I->click('.motionTypeCreate a');
$I->checkOption('.presetMotion');
$I->seeInField('#typeTitleSingular', 'Antrag');
$I->fillField('#typeTitleSingular', 'Hidden motion');
$I->fillField('#typeTitlePlural', 'Hidden motions');
$I->fillField('#typeCreateTitle', 'Create hidden');
$I->submitForm('.motionTypeCreateForm', [], 'create');

$I->clickJS('.sectionAdder');
$I->selectOption('#sectionTypenew0', ISectionType::TYPE_TEXT_SIMPLE);
$I->fillField('.sectionnew0 .sectionTitle input', 'Message to the admin');
$I->dontSeeCheckboxIsChecked('.sectionnew0 .nonPublicRow input');
$I->seeElement('.sectionnew0 .amendmentRow');
$I->checkOption('.sectionnew0 .nonPublicRow input');
$I->wait(0.1);
$I->dontSeeElement('.sectionnew0 .amendmentRow');
$I->submitForm('.adminTypeForm', [], 'save');

$I->seeCheckboxIsChecked('.section' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 3) . ' .nonPublicRow input');


$I->wantTo('Enable the API');
$I->gotoStdAdminPage()->gotoAppearance();
$I->executeJS('$("#apiEnabled").prop("checked", true).trigger("change");');
$I->submitForm('#consultationAppearanceForm', [], 'save');


$I->wantTo('Create a motion');
$I->gotoConsultationHome();
$I->click('#sidebar .createMotion' . AcceptanceTester::FIRST_FREE_MOTION_TYPE);
$I->see('nur für dich und Administrierende', '#section_holder_54');
$I->fillField('#sections_' . AcceptanceTester::FIRST_FREE_MOTION_SECTION, 'New motion');
$I->executeJS('CKEDITOR.instances.sections_' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 1) . '_wysiwyg.setData("<p>Public text</p>")');
$I->executeJS('CKEDITOR.instances.sections_' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 2) . '_wysiwyg.setData("<p>Reason</p>")');
$I->executeJS('CKEDITOR.instances.sections_' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 3) . '_wysiwyg.setData("<p>Internal hint for the admins</p>")');
$I->uncheckOption('input[name=otherInitiator]');
$I->fillField('#initiatorPrimaryName', 'My name');
$I->fillField('#initiatorEmail', 'test@example.org');
$I->submitForm('#motionEditForm', [], 'save');

$I->see('nur für dich und Administrierende');
$I->see('Internal hint for the admins');
$I->submitForm('#motionConfirmForm', [], 'confirm');


$I->wantTo('Check that the hidden section is visible for me');
$I->gotoConsultationHome()->gotoMotionView(AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->see('Public text', '#section_' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 1));
$I->see('nur für dich als Antragsteller*in', '#section_' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 3));
$I->see('Internal hint for the admins', '#section_' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 3));

$I->click('#sidebar .adminEdit');
$I->clickJS('#motionTextEditCaller button');
$I->wait(1);
$content = $I->executeJS('return document.getElementById("section_holder_54").innerText');
$I->assertStringContainsString('Internal hint for the admins', $content);


$I->wantTo('Check that the hidden section is not visible for someone else');

$I->gotoConsultationHome();
$I->logout();
$I->loginAsStdUser();
$I->click('.motionLink' . AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->see('Public text', '#section_' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 1));
$I->dontSee('nur für dich als Antragsteller*in');
$I->dontSee('Internal hint for the admins');


$I->wantTo('Check that the hidden section is not shown at the API');

$baseUri = str_replace(['{SUBDOMAIN}', '{PATH}'], ['stdparteitag', ''], AcceptanceTester::ABSOLUTE_URL_TEMPLATE_SITE);
$client = new Client([
    'base_uri' => $baseUri,
    RequestOptions::HTTP_ERRORS => false,
]);
$request = $client->get('rest/std-parteitag');
$consultationRest = json_decode($request->getBody()->getContents(), true);
$motion = $consultationRest['motion_links'][count($consultationRest['motion_links']) - 1];
$I->assertSame('New motion', $motion['title']);
$urlJsonParts = explode('stdparteitag/', $motion['url_json']);

$request = $client->get($urlJsonParts[1]);
$I->assertEquals(200, $request->getStatusCode());
$motionRest = json_decode($request->getBody()->getContents(), true);
$I->assertCount(2, $motionRest['sections']);
$I->assertSame('Antragstext', $motionRest['sections'][0]['title']);
$I->assertSame('Begründung', $motionRest['sections'][1]['title']);
