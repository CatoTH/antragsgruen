<?php

/** @var \Codeception\Scenario $scenario */
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();
$I->apiSetApiEnabled();

$token = $I->apiLoginAsStdAdmin();

$client = new Client([
    'base_uri' => str_replace(['{SUBDOMAIN}', '{PATH}'], ['stdparteitag', ''], AcceptanceTester::ABSOLUTE_URL_TEMPLATE_SITE),
    RequestOptions::HTTP_ERRORS => false,
]);

$request = $client->get('rest/std-parteitag/motion-types', [RequestOptions::HEADERS => [
    'Authorization' => 'Bearer ' . $token,
]]);
$I->assertEquals(200, $request->getStatusCode());


$expectedMotionTypeData = file_get_contents(__DIR__ . '/../../Support/Data/api-motion-types-default.json');
$I->assertJsonStringEqualsJsonString($expectedMotionTypeData, $request->getBody()->getContents());


// Update the motion type: motions now need a collecting phase (at least one official supporter, given by a
// logged-in user) before they can be published.

$token = $I->apiLoginAsStdAdmin();

$typeUpdateData = [
    'policies' => [
        'motions' => ['id' => 'all'],
        'amendments' => ['id' => 'all'],
        'comments' => ['id' => 'all'],
        'support_motions' => ['id' => 'logged_in'],
        'support_amendments' => ['id' => 'nobody'],
    ],
    'motion_likes_dislikes' => ['support'],
    'motion_initiator_settings' => [
        'type' => 'collecting_supporters',
        'initiator_can_be_person' => true,
        'initiator_can_be_organization' => true,
        'person_policy' => ['id' => 'all'],
        'organization_policy' => ['id' => 'all'],
        'min_supporters' => 1,
        'allow_more_supporters' => true,
        'allow_supporting_after_publication' => false,
        'offer_non_public_supports' => false,
        'has_organizations' => true,
        'contact_name' => 'none',
        'contact_email' => 'required',
        'contact_phone' => 'optional',
        'contact_gender' => 'none',
        'has_resolution_date' => 'required',
    ],
];

$request = $client->patch('rest/std-parteitag/motion-types/1', [
    RequestOptions::HEADERS => ['Authorization' => 'Bearer ' . $token],
    RequestOptions::JSON => $typeUpdateData,
]);
$I->assertEquals(200, $request->getStatusCode());

$updatedType = json_decode($request->getBody()->getContents(), true);
$I->assertSame('logged_in', $updatedType['policies']['support_motions']['id']);


// Create a motion, based on the (now updated) motion type definition above.
// Created by a regular, non-privileged user: with PRIVILEGE_MOTION_INITIATORS (e.g. an admin), the initiator
// would not actually be attributed to any user account, which we need for the "self-support" check below.

$token = $I->apiLoginAsStdUser();

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
$I->assertSame(\app\models\db\IMotion::STATUS_COLLECTING_SUPPORTERS, $created['status_id']);
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
$I->assertSame(\app\models\db\IMotion::STATUS_COLLECTING_SUPPORTERS, $fetched['status_id']);

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


$I->wantTo('try to officially support the motion as its own initiator - this must fail');

$request = $client->post($created['url_json'] . '/support', [
    RequestOptions::HEADERS => ['Authorization' => 'Bearer ' . $token], // still the initiator's (testuser's) token
    RequestOptions::JSON => ['name' => 'Testuser', 'organization' => 'Test Organization'],
]);
$I->assertEquals(403, $request->getStatusCode());
$selfSupportError = json_decode($request->getBody()->getContents(), true);
$I->assertFalse($selfSupportError['success']);


$I->wantTo('support the motion as a different, regular user - this must succeed');

$fixedDataToken = $I->apiLoginAsFixedDataUser();

$request = $client->post($created['url_json'] . '/support', [
    RequestOptions::HEADERS => ['Authorization' => 'Bearer ' . $fixedDataToken],
    RequestOptions::JSON => ['name' => 'Fixed Data', 'organization' => 'MotionTools'],
]);
$I->assertEquals(200, $request->getStatusCode());

$supported = json_decode($request->getBody()->getContents(), true);
$I->assertCount(1, $supported['supporters']);
$I->assertSame('Fixed Data', $supported['supporters'][0]['name']);

