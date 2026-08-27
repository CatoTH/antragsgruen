export const FIRST_FREE_MOTION_ID = 121;
export const FIRST_FREE_MOTION_TITLE_PREFIX = 'A9';
export const FIRST_FREE_AMENDMENT_TITLE_PREFIX = 'Ä8';
export const FIRST_FREE_MOTION_SECTION = 51;
export const FIRST_FREE_AMENDMENT_ID = 284;
export const FIRST_FREE_AGENDA_ITEM_ID = 15;
export const FIRST_FREE_COMMENT_ID = 1;
export const FIRST_FREE_MOTION_TYPE = 17;
export const FIRST_FREE_CONSULTATION_ID = 11;
export const FIRST_FREE_VOTING_BLOCK_ID = 3;
export const FIRST_FREE_CONTENT_ID = 4;
export const FIRST_FREE_USER_ID = 10;
export const FIRST_FREE_TAG_ID = 11;
export const FIRST_FREE_USERGROUP_ID = 40;

export const ABSOLUTE_URL_DOMAIN = 'test.antragsgruen.test';
export const ABSOLUTE_URL_TEMPLATE_SITE =
    'http://test.antragsgruen.test/{SUBDOMAIN}/{PATH}';
export const ABSOLUTE_URL_TEMPLATE =
    'http://test.antragsgruen.test/{SUBDOMAIN}/{CONSULTATION}/{PATH}';

export const DEFAULT_SUBDOMAIN = 'stdparteitag';
export const DEFAULT_CONSULTATION_PATH = 'std-parteitag';

export const ACCEPTED_HTML_ERRORS = [
    'Bad value “popup” for attribute “rel”',
    'Attribute “value” not allowed on element “li” at this point',
    'CKEDITOR',
    'autocomplete',
];

export function buildUrl(
    subdomain: string,
    consultation: string,
    path: string = '',
): string {
    return `/${subdomain}/${consultation}/${path}`.replace(/\/+$/, '/');
}