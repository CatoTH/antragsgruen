<?php

namespace Tests\Support;

/**
 * Shared test-fixture constants.
 *
 * Originally lived on `AcceptanceTester` (the Codeception actor class).
 * When the acceptance suite was migrated to Playwright, that actor was
 * deleted and the constants moved to `e2e/utils/constants.ts`. The Unit
 * tests still need these values because they share the same `dbdata1.sql`
 * fixture and assert against the IDs/prefixes that fixture assigns.
 *
 * Keep this file in sync with `e2e/utils/constants.ts`.
 */
final class TestFixtures
{
    public const FIRST_FREE_MOTION_ID              = 121;
    public const FIRST_FREE_MOTION_TITLE_PREFIX    = 'A9';
    public const FIRST_FREE_AMENDMENT_TITLE_PREFIX = 'Ä8';
    public const FIRST_FREE_MOTION_SECTION         = 51;
    public const FIRST_FREE_AMENDMENT_ID           = 284;
    public const FIRST_FREE_AGENDA_ITEM_ID         = 15;
    public const FIRST_FREE_COMMENT_ID             = 1;
    public const FIRST_FREE_MOTION_TYPE            = 17;
    public const FIRST_FREE_CONSULTATION_ID        = 11;
    public const FIRST_FREE_VOTING_BLOCK_ID        = 3;
    public const FIRST_FREE_CONTENT_ID             = 4;
    public const FIRST_FREE_USER_ID                = 10;
    public const FIRST_FREE_TAG_ID                 = 14;
    public const FIRST_FREE_USERGROUP_ID           = 40;

    public const ABSOLUTE_URL_DOMAIN = 'test.antragsgruen.test';
    public const ABSOLUTE_URL_TEMPLATE_SITE = 'http://test.antragsgruen.test/{SUBDOMAIN}/{PATH}';
    public const ABSOLUTE_URL_TEMPLATE = 'http://test.antragsgruen.test/{SUBDOMAIN}/{CONSULTATION}/{PATH}';
}