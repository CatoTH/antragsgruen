import { test as base, Page, APIRequestContext } from '@playwright/test';
import { DBFixture, FixtureName, resetConfig } from '../utils/test-api';
import {
    clearLocalStorage,
    loginAsStdAdmin,
    loginAsStdUser,
    loginAsConsultationAdmin,
    loginAsProposalAdmin,
    loginAsProgressAdmin,
    loginAsGlobalAdmin,
    loginAsFixedDataAdmin,
    loginAsFixedDataUser,
} from '../utils/auth';
import { DEFAULT_CONSULTATION_PATH, DEFAULT_SUBDOMAIN } from '../utils/constants';

type AntragsgruenFixtures = {
    db: DBFixture;
    freshDB: void;
    resetConfig: void;
};

export const test = base.extend<AntragsgruenFixtures>({
    db: async ({ request }, use) => {
        await use(new DBFixture(request));
    },

    // config_tests.json is a file, so setConfig() survives populate-db and would otherwise leak
    // into every later test of the run. Reset it before each test, as ConfigurationChanger does
    // for the Codeception suite.
    resetConfig: [
        async ({ request }, use) => {
            await resetConfig(request);
            await use();
        },
        { auto: true },
    ],

    freshDB: [
        async ({ request }, use) => {
            const db = new DBFixture(request);
            await db.populate('dbdata1');
            await use();
            await db.reset();
        },
        { auto: false },
    ],
});

export { expect } from '@playwright/test';
export type { Page, APIRequestContext };

export async function bootstrapConsultation(
    page: Page,
    options: {
        fixture?: FixtureName;
        subdomain?: string;
        consultationPath?: string;
        loginAs?:
            | 'stdAdmin'
            | 'stdUser'
            | 'consultationAdmin'
            | 'proposalAdmin'
            | 'progressAdmin'
            | 'globalAdmin'
            | 'fixedDataAdmin'
            | 'fixedDataUser';
        clearLocalStorage?: boolean;
    } = {},
): Promise<void> {
    const subdomain = options.subdomain ?? DEFAULT_SUBDOMAIN;
    const path = options.consultationPath ?? DEFAULT_CONSULTATION_PATH;

    await page.goto(`/${subdomain}/${path}`);

    if (options.clearLocalStorage !== false) {
        await clearLocalStorage(page);
    }

    if (options.loginAs) {
        switch (options.loginAs) {
            case 'stdAdmin':
                await loginAsStdAdmin(page);
                break;
            case 'stdUser':
                await loginAsStdUser(page);
                break;
            case 'consultationAdmin':
                await loginAsConsultationAdmin(page);
                break;
            case 'proposalAdmin':
                await loginAsProposalAdmin(page);
                break;
            case 'progressAdmin':
                await loginAsProgressAdmin(page);
                break;
            case 'globalAdmin':
                await loginAsGlobalAdmin(page);
                break;
            case 'fixedDataAdmin':
                await loginAsFixedDataAdmin(page);
                break;
            case 'fixedDataUser':
                await loginAsFixedDataUser(page);
                break;
        }
    }
}