<?php

/** @var \Codeception\Scenario $scenario */
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsStdUser();

$grabToken = function () use ($I): string {
    $I->amOnPage(str_replace(
        ['{SUBDOMAIN}', '{CONSULTATION}', '{PATH}'],
        ['stdparteitag', 'std-parteitag', 'token'],
        AcceptanceTester::ABSOLUTE_URL_TEMPLATE
    ));
    $text = explode('"token":"', $I->grabPageSource());

    return explode('"', $text[1])[0];
};
$token = $grabToken();


$client = new Client([
    'base_uri' => str_replace(['{SUBDOMAIN}', '{PATH}'], ['stdparteitag', ''], AcceptanceTester::ABSOLUTE_URL_TEMPLATE_SITE),
    RequestOptions::HTTP_ERRORS => false,
]);

$request = $client->get('rest/std-parteitag/motion-types', [RequestOptions::HEADERS => [
    'Authorization' => 'Bearer ' . $token,
]]);
$I->assertEquals(200, $request->getStatusCode());


$I->assertJsonStringEqualsJsonString('{
  "items": [
    {
      "id": 1,
      "labels": {
        "singular": "Antrag",
        "plural": "Antr\u00e4ge",
        "create": "Antrag stellen"
      },
      "settings": {
        "amendments_only": false,
        "amendment_multiple_paragraphs": "multiple",
        "has_proposed_procedure": true,
        "has_responsibilities": false,
        "allow_amendments_to_amendments": false,
        "merging_deadlines": []
      },
      "policies": {
        "motions": {
          "id": "all",
          "description": "Alle",
          "deadlines": [],
          "user_group_ids": null
        },
        "amendments": {
          "id": "all",
          "description": "Alle",
          "deadlines": [],
          "user_group_ids": null
        },
        "comments": {
          "id": "all",
          "description": "Alle",
          "deadlines": [],
          "user_group_ids": null
        },
        "support_motions": {
          "id": "nobody",
          "description": "Niemand",
          "deadlines": [],
          "user_group_ids": null
        },
        "support_amendments": {
          "id": "nobody",
          "description": "Niemand",
          "deadlines": [],
          "user_group_ids": null
        }
      },
      "sections": [
        {
          "id": 1,
          "type": "Title",
          "title": "\u00dcberschrift",
          "required": "yes",
          "max_len": 0,
          "line_numbers": true,
          "has_amendments": true,
          "has_comments": "none",
          "position_right": false
        },
        {
          "id": 2,
          "type": "TextSimple",
          "title": "Antragstext",
          "required": "yes",
          "max_len": 0,
          "line_numbers": true,
          "has_amendments": true,
          "has_comments": "motion",
          "position_right": false
        },
        {
          "id": 4,
          "type": "TextSimple",
          "title": "Antragstext 2",
          "required": "no",
          "max_len": 0,
          "line_numbers": true,
          "has_amendments": true,
          "has_comments": "motion",
          "position_right": false
        },
        {
          "id": 3,
          "type": "TextSimple",
          "title": "Begr\u00fcndung",
          "required": "no",
          "max_len": 0,
          "line_numbers": false,
          "has_amendments": false,
          "has_comments": "none",
          "position_right": false
        },
        {
          "id": 5,
          "type": "Image",
          "title": "Abbildung",
          "required": "no",
          "max_len": 0,
          "line_numbers": true,
          "has_amendments": false,
          "has_comments": "none",
          "position_right": false
        }
      ],
      "motion_prefix": "A"
    }
  ]
}', $request->getBody()->getContents());


// Create a motion, based on the motion type definition above

$motionTitle = 'Testing REST motion creation';
$motionText = '<p>This is the motion text, submitted via the REST API.</p>';
$motionReason = '<p>This is the reason for the motion.</p>';

$createData = [
    'motion_type_id' => 1,
    'agenda_item_id' => null,
    'sections' => [
        ['section_id' => 1, 'data' => $motionTitle],
        ['section_id' => 2, 'data' => $motionText],
        ['section_id' => 3, 'data' => $motionReason],
    ],
    'initiators' => [
        [
            'person_type' => 'person',
            'name' => 'Rest Testuser',
            'organization' => 'Test Organization',
            'contact_email' => 'rest-testuser@example.org',
            'contact_phone' => '+49 30 12345678',
        ],
    ],
];

$request = $client->post('rest/std-parteitag/motion', [
    RequestOptions::HEADERS => ['Authorization' => 'Bearer ' . $token],
    RequestOptions::JSON => $createData,
]);
$I->assertEquals(201, $request->getStatusCode());

$created = json_decode($request->getBody()->getContents(), true);
$I->assertSame('motion', $created['type']);
$I->assertIsInt($created['id']);
$I->assertGreaterThan(0, $created['id']);
$I->assertSame(\app\models\db\IMotion::STATUS_SUBMITTED_SCREENED, $created['status_id']);
$I->assertSame($motionTitle, $created['title']);
$I->assertNotEmpty($created['url_json']);


$I->wantTo('retrieve the motion');
$request = $client->get($created['url_json'], [RequestOptions::HEADERS => [
    'Authorization' => 'Bearer ' . $token,
]]);
$I->assertEquals(200, $request->getStatusCode());
$fetched = json_decode($request->getBody()->getContents(), true);


// Compare the fetched motion with the submitted data

$I->assertSame($created['id'], $fetched['id']);
$I->assertSame($motionTitle, $fetched['title']);
$I->assertSame(\app\models\db\IMotion::STATUS_SUBMITTED_SCREENED, $fetched['status_id']);

$sectionsByTitle = [];
foreach ($fetched['sections'] as $section) {
    $sectionsByTitle[$section['title']] = $section;
}
$I->assertSame('TextSimple', $sectionsByTitle['Antragstext']['type']);
$I->assertSame('<div class="text motionTextFormattings textOrig">' . $motionText . '</div>', $sectionsByTitle['Antragstext']['html']);
$I->assertSame('<div class="text motionTextFormattings textOrig">' . $motionReason . '</div>', $sectionsByTitle['Begründung']['html']);

$I->assertCount(1, $fetched['initiators']);
$I->assertSame('person', $fetched['initiators'][0]['type']);
$I->assertSame('Rest Testuser', $fetched['initiators'][0]['name']);
$I->assertSame('Test Organization', $fetched['initiators'][0]['organization']);
$I->assertSame([], $fetched['supporters']);
$I->assertSame([], $fetched['amendment_links']);


// The GET response should match what the create endpoint returned
// (except pagination, as the prev/next links depend on the requesting user's visibility)

unset($created['pagination'], $fetched['pagination']);
$I->assertEquals($created, $fetched);

