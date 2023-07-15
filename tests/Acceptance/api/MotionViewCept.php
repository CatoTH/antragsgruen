<?php

/** @var \Codeception\Scenario $scenario */
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$baseUri = str_replace(['{SUBDOMAIN}', '{PATH}'], ['stdparteitag', ''], AcceptanceTester::ABSOLUTE_URL_TEMPLATE_SITE);
$client = new Client([
    'base_uri' => $baseUri,
    RequestOptions::HTTP_ERRORS => false,
]);

// Default: API is disabled

$request = $client->get('rest/std-parteitag/motion/Testing_proposed_changes-630/amendment/283');

$I->assertEquals(403, $request->getStatusCode());
$I->assertJsonStringEqualsJsonString('{"success":false,"message":"Public API disabled"}', $request->getBody()->getContents());


// Enable it

$I->loginAndGotoStdAdminPage()->gotoAppearance();
$I->executeJS('$("#apiEnabled").prop("checked", true).trigger("change");');
$I->submitForm('#consultationAppearanceForm', [], 'save');


// Check that the API is now returning the correct result

$request = $client->get('rest/std-parteitag/motion/Testing_proposed_changes-630/amendment/283');

$baseUri = 'http://' . AcceptanceTester::ABSOLUTE_URL_DOMAIN . '/stdparteitag/'; // "stdparteitag" is the subdomain
$I->assertEquals(200, $request->getStatusCode());
$I->assertJsonStringEqualsJsonString('{
    "type": "amendment",
    "id": 283,
    "prefix": "\u00c44",
    "title": "\u00c44 zu A8: Testing proposed changes",
    "title_with_prefix": "\u00c44 zu A8: Testing proposed changes",
    "first_line": 24,
    "status_id": 3,
    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
    "date_published": "2018-11-03T06:14:01+00:00",
    "motion": {
        "id": 118,
        "agenda_item": null,
        "prefix": "A8",
        "title": "Testing proposed changes",
        "title_with_intro": "Testing proposed changes",
        "title_with_prefix": "A8: Testing proposed changes",
        "initiators_html": "Testuser",
        "url_json": "' . $baseUri . 'rest/std-parteitag/motion/Testing_proposed_changes-630",
        "url_html": "' . $baseUri . 'std-parteitag/Testing_proposed_changes-630"
    },
    "supporters": [],
    "initiators": [
        {
            "type": "person",
            "name": "Testuser",
            "organization": ""
        }
    ],
    "initiators_html": "Testuser",
    "sections": [
        {
            "type": "TextSimple",
            "title": "Antragstext",
            "html": "<div class=\"text motionTextFormattings textOrig\"><h4 class=\"lineSummary\">Von Zeile 23 bis 25:</h4><div><p>et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. <del style=\"color:#FF0000;text-decoration:line-through;\">Lorem</del><ins style=\"color:#008000;text-decoration:underline;\">Zombie</ins> ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et </p></div></div>"
        },
        {
            "type": "TextSimple",
            "title": "Antragstext 2",
            "html": ""
        }
    ],
    "proposed_procedure": null,
    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/Testing_proposed_changes-630/amendment/283",
    "url_html": "' . $baseUri . 'std-parteitag/Testing_proposed_changes-630/283"
}', $request->getBody()->getContents());
