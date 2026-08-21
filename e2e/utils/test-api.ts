import { APIRequestContext, Page } from '@playwright/test';
import {
    ABSOLUTE_URL_TEMPLATE_SITE,
    DEFAULT_CONSULTATION_PATH,
    DEFAULT_SUBDOMAIN,
} from './constants';

const TEST_CONTROLLER_TIMEOUT = 60_000;

export type FixtureName = 'dbdata1' | 'dbdata-yfj' | 'dbdata-dbwv';

async function post(
    request: APIRequestContext,
    operation: string,
    data: Record<string, any> = {},
): Promise<any> {
    const response = await request.post(`/test/${operation}`, {
        form: data,
        timeout: TEST_CONTROLLER_TIMEOUT,
    });
    if (!response.ok()) {
        throw new Error(
            `Test endpoint /test/${operation} failed: ${response.status()} ${await response.text()}`,
        );
    }
    const ct = response.headers()['content-type'] ?? '';
    if (!ct.toLowerCase().includes('application/json')) {
        const body = await response.text();
        throw new Error(
            `/test/${operation} returned non-JSON (${ct}; expected application/json): ${body.slice(0, 500)}`,
        );
    }
    return response.json();
}

export async function populateDB(
    request: APIRequestContext,
    fixture: FixtureName = 'dbdata1',
): Promise<void> {
    await post(request, 'populate-db', { fixture });
}

export async function resetDB(request: APIRequestContext): Promise<void> {
    await post(request, 'reset-db');
}

export class DBFixture {
    constructor(private readonly request: APIRequestContext) {}

    async populate(fixture: FixtureName = 'dbdata1'): Promise<void> {
        await populateDB(this.request, fixture);
    }

    async reset(): Promise<void> {
        await resetDB(this.request);
    }
}

export async function setConfig(
    request: APIRequestContext,
    values: Record<string, any>,
): Promise<void> {
    await post(request, 'set-config', values);
}

export async function setApiEnabled(
    request: APIRequestContext,
    enabled: boolean = true,
    subdomain: string = DEFAULT_SUBDOMAIN,
    consultationUrl: string = DEFAULT_CONSULTATION_PATH,
): Promise<void> {
    await post(request, 'set-api-enabled', {
        subdomain,
        consultationUrl,
        enabled: enabled ? '1' : '0',
    });
}

export async function setAmendmentStatus(
    request: APIRequestContext,
    amendmentId: number,
    status: number,
    subdomain: string = DEFAULT_SUBDOMAIN,
    consultationUrl: string = DEFAULT_CONSULTATION_PATH,
): Promise<void> {
    await post(request, 'set-amendment-status', {
        subdomain,
        consultationUrl,
        id: String(amendmentId),
        status: String(status),
    });
}

export async function setUserFixedData(
    request: APIRequestContext,
    params: {
        email: string;
        nameGiven?: string;
        nameFamily?: string;
        organisation?: string;
        fixed?: boolean;
    },
    subdomain: string = DEFAULT_SUBDOMAIN,
    consultationUrl: string = DEFAULT_CONSULTATION_PATH,
): Promise<void> {
    await post(request, 'set-user-fixed-data', {
        subdomain,
        consultationUrl,
        ...params,
        fixed: params.fixed ? '1' : '0',
    });
}

export async function setUserVoted(
    request: APIRequestContext,
    params: {
        email: string;
        votingBlock: number;
        itemId: number;
        answer: string;
    },
    subdomain: string = DEFAULT_SUBDOMAIN,
    consultationUrl: string = DEFAULT_CONSULTATION_PATH,
): Promise<void> {
    await post(request, 'user-votes', {
        subdomain,
        consultationUrl,
        ...params,
    });
}

export async function getTotpCode(request: APIRequestContext): Promise<string> {
    const response = await request.post('/test/totp-code', {
        form: {},
        timeout: TEST_CONTROLLER_TIMEOUT,
    });
    if (!response.ok()) {
        throw new Error(
            `Test endpoint /test/totp-code failed: ${response.status()} ${await response.text()}`,
        );
    }
    const data = await response.json();
    if (!data.ok) {
        throw new Error(`totp-code error: ${data.error}`);
    }
    return data.code;
}

export function testSiteUrl(
    subdomain: string = DEFAULT_SUBDOMAIN,
    path: string = '',
): string {
    return ABSOLUTE_URL_TEMPLATE_SITE.replace('{SUBDOMAIN}', subdomain).replace(
        '{PATH}',
        path,
    );
}

export async function submitForm(
    page: Page,
    formSelector: string,
    buttonName: string = '',
): Promise<void> {
    const button = buttonName
        ? page.locator(`${formSelector} [name="${buttonName}"]`).first()
        : page.locator(`${formSelector} [type="submit"]`).first();
    await button.click();
}