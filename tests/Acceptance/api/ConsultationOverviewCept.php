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

$request = $client->get('rest/std-parteitag');

$I->assertEquals(403, $request->getStatusCode());
$I->assertJsonStringEqualsJsonString('{"success":false,"message":"Public API disabled"}', $request->getBody()->getContents());


// Enable it

$I->loginAndGotoStdAdminPage()->gotoAppearance();
$I->executeJS('$("#apiEnabled").prop("checked", true).trigger("change");');
$I->submitForm('#consultationAppearanceForm', [], 'save');


// Check that the API is now returning the correct result

$request = $client->get('rest/std-parteitag');

$I->assertEquals(200, $request->getStatusCode());
$I->assertJsonStringEqualsJsonString('{
    "title": "Test2",
    "title_short": "Test2",
    "speaking_lists": null,
    "page_links": [],
    "motion_links": [
        {
            "id": 2,
            "agenda_item": null,
            "prefix": "A2",
            "title": "O\u2019zapft is!",
            "title_with_intro": "O\u2019zapft is!",
            "title_with_prefix": "A2: O\u2019zapft is!",
            "type": "motion",
            "status_id": 3,
            "status_title": "Eingereicht",
            "initiators_html": "Testuser",
            "amendment_links": [
                {
                    "id": 1,
                    "prefix": "\u00c41",
                    "status_id": 3,
                    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
                    "initiators_html": "Tester",
                    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/321-o-zapft-is/amendment/1",
                    "url_html": "' . $baseUri . 'std-parteitag/motion/321-o-zapft-is/amendment/1"
                },
                {
                    "id": 3,
                    "prefix": "\u00c42",
                    "status_id": 3,
                    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
                    "initiators_html": "Testadmin",
                    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/321-o-zapft-is/amendment/3",
                    "url_html": "' . $baseUri . 'std-parteitag/motion/321-o-zapft-is/amendment/3"
                },
                {
                    "id": 270,
                    "prefix": "\u00c43",
                    "status_id": 3,
                    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
                    "initiators_html": "Tester",
                    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/321-o-zapft-is/amendment/270",
                    "url_html": "' . $baseUri . 'std-parteitag/motion/321-o-zapft-is/amendment/270"
                },
                {
                    "id": 272,
                    "prefix": "\u00c44",
                    "status_id": 3,
                    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
                    "initiators_html": "Luca Lischke",
                    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/321-o-zapft-is/amendment/272",
                    "url_html": "' . $baseUri . 'std-parteitag/motion/321-o-zapft-is/amendment/272"
                },
                {
                    "id": 273,
                    "prefix": "\u00c45",
                    "status_id": 3,
                    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
                    "initiators_html": "Luca Lischke",
                    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/321-o-zapft-is/amendment/273",
                    "url_html": "' . $baseUri . 'std-parteitag/motion/321-o-zapft-is/amendment/273"
                },
                {
                    "id": 274,
                    "prefix": "\u00c46",
                    "status_id": 3,
                    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
                    "initiators_html": "Tester",
                    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/321-o-zapft-is/amendment/274",
                    "url_html": "' . $baseUri . 'std-parteitag/motion/321-o-zapft-is/amendment/274"
                },
                {
                    "id": 276,
                    "prefix": "\u00c47",
                    "status_id": 3,
                    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
                    "initiators_html": "Testuser",
                    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/321-o-zapft-is/amendment/276",
                    "url_html": "' . $baseUri . 'std-parteitag/motion/321-o-zapft-is/amendment/276"
                }
            ],
            "url_json": "' . $baseUri . 'rest/std-parteitag/motion/321-o-zapft-is",
            "url_html": "' . $baseUri . 'std-parteitag/motion/321-o-zapft-is"
        },
        {
            "id": 3,
            "agenda_item": null,
            "prefix": "A3",
            "title": "Textformatierungen",
            "title_with_intro": "Textformatierungen",
            "title_with_prefix": "A3: Textformatierungen",
            "type": "motion",
            "status_id": 3,
            "status_title": "Eingereicht",
            "initiators_html": "Testadmin",
            "amendment_links": [
                {
                    "id": 2,
                    "prefix": "\u00c41",
                    "status_id": 3,
                    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
                    "initiators_html": "Testuser (beschlossen am: 17.07.2015)",
                    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/123-textformatierungen/amendment/2",
                    "url_html": "' . $baseUri . 'std-parteitag/motion/123-textformatierungen/amendment/2"
                }
            ],
            "url_json": "' . $baseUri . 'rest/std-parteitag/motion/123-textformatierungen",
            "url_html": "' . $baseUri . 'std-parteitag/motion/123-textformatierungen"
        },
        {
            "id": 58,
            "agenda_item": null,
            "prefix": "A4",
            "title": "Testantrag",
            "title_with_intro": "Testantrag",
            "title_with_prefix": "A4: Testantrag",
            "type": "motion",
            "status_id": 3,
            "status_title": "Eingereicht",
            "initiators_html": "Testuser",
            "amendment_links": [],
            "url_json": "' . $baseUri . 'rest/std-parteitag/motion/58",
            "url_html": "' . $baseUri . 'std-parteitag/motion/58"
        },
        {
            "id": 114,
            "agenda_item": null,
            "prefix": "A5",
            "title": "Leerzeichen-Test",
            "title_with_intro": "Leerzeichen-Test",
            "title_with_prefix": "A5: Leerzeichen-Test",
            "type": "motion",
            "status_id": 3,
            "status_title": "Eingereicht",
            "initiators_html": "Tester",
            "amendment_links": [],
            "url_json": "' . $baseUri . 'rest/std-parteitag/motion/114",
            "url_html": "' . $baseUri . 'std-parteitag/motion/114"
        },
        {
            "id": 115,
            "agenda_item": null,
            "prefix": "A6",
            "title": "Listen-Test",
            "title_with_intro": "Listen-Test",
            "title_with_prefix": "A6: Listen-Test",
            "type": "motion",
            "status_id": 3,
            "status_title": "Eingereicht",
            "initiators_html": "Testuser",
            "amendment_links": [
                {
                    "id": 277,
                    "prefix": "\u00c41",
                    "status_id": 3,
                    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
                    "initiators_html": "Testuser",
                    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/115/amendment/277",
                    "url_html": "' . $baseUri . 'std-parteitag/motion/115/amendment/277"
                }
            ],
            "url_json": "' . $baseUri . 'rest/std-parteitag/motion/115",
            "url_html": "' . $baseUri . 'std-parteitag/motion/115"
        },
        {
            "id": 117,
            "agenda_item": null,
            "prefix": "A7",
            "title": "Moving test",
            "title_with_intro": "Moving test",
            "title_with_prefix": "A7: Moving test",
            "type": "motion",
            "status_id": 3,
            "status_title": "Eingereicht",
            "initiators_html": "Testuser (Anonymous)",
            "amendment_links": [
                {
                    "id": 278,
                    "prefix": "\u00c41",
                    "status_id": 3,
                    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
                    "initiators_html": "Mover (Moving)",
                    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/Moving_test-47262/amendment/278",
                    "url_html": "' . $baseUri . 'std-parteitag/Moving_test-47262/278"
                }
            ],
            "url_json": "' . $baseUri . 'rest/std-parteitag/motion/Moving_test-47262",
            "url_html": "' . $baseUri . 'std-parteitag/Moving_test-47262"
        },
        {
            "id": 118,
            "agenda_item": null,
            "prefix": "A8",
            "title": "Testing proposed changes",
            "title_with_intro": "Testing proposed changes",
            "title_with_prefix": "A8: Testing proposed changes",
            "type": "motion",
            "status_id": 3,
            "status_title": "Eingereicht",
            "initiators_html": "Testuser",
            "amendment_links": [
                {
                    "id": 279,
                    "prefix": "\u00c41",
                    "status_id": 3,
                    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
                    "initiators_html": "Testuser",
                    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/Testing_proposed_changes-630/amendment/279",
                    "url_html": "' . $baseUri . 'std-parteitag/Testing_proposed_changes-630/279"
                },
                {
                    "id": 280,
                    "prefix": "\u00c42",
                    "status_id": 3,
                    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
                    "initiators_html": "Testuser",
                    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/Testing_proposed_changes-630/amendment/280",
                    "url_html": "' . $baseUri . 'std-parteitag/Testing_proposed_changes-630/280"
                },
                {
                    "id": 281,
                    "prefix": "\u00c43",
                    "status_id": 3,
                    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
                    "initiators_html": "Testuser (Test)",
                    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/Testing_proposed_changes-630/amendment/281",
                    "url_html": "' . $baseUri . 'std-parteitag/Testing_proposed_changes-630/281"
                },
                {
                    "id": 283,
                    "prefix": "\u00c44",
                    "status_id": 3,
                    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
                    "initiators_html": "Testuser",
                    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/Testing_proposed_changes-630/amendment/283",
                    "url_html": "' . $baseUri . 'std-parteitag/Testing_proposed_changes-630/283"
                }
            ],
            "url_json": "' . $baseUri . 'rest/std-parteitag/motion/Testing_proposed_changes-630",
            "url_html": "' . $baseUri . 'std-parteitag/Testing_proposed_changes-630"
        }
    ],
    "url_json": "' . $baseUri . 'rest/std-parteitag",
    "url_html": "' . $baseUri . 'std-parteitag"
}', $request->getBody()->getContents());
