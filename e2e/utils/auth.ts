import { Page, APIRequestContext } from '@playwright/test';
import {
    DEFAULT_CONSULTATION_PATH,
    DEFAULT_SUBDOMAIN,
} from './constants';

async function loginWithCredentials(
    page: Page,
    username: string,
    password: string,
): Promise<void> {
    const baseUrl = process.env.E2E_BASE_URL || 'http://test.antragsgruen.test';
    const currentUrl = page.url();
    if (currentUrl === 'about:blank' || !currentUrl.startsWith(baseUrl)) {
        await page.goto(`/${DEFAULT_SUBDOMAIN}/${DEFAULT_CONSULTATION_PATH}`);
    }
    await page.locator('#loginLink').waitFor({ state: 'visible', timeout: 10_000 });
    await page.locator('#loginLink').click();
    await page.locator('h1').filter({ hasText: /LOGIN/i }).waitFor();
    await page.locator('#username').fill(username);
    await page.locator('#passwordInput').fill(password);
    await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
    await page.locator('#logoutLink').waitFor({ state: 'visible', timeout: 10_000 });
}

export const loginAsStdAdmin = (page: Page) =>
    loginWithCredentials(page, 'testadmin@example.org', 'testadmin');

export const loginAsConsultationAdmin = (page: Page) =>
    loginWithCredentials(page, 'consultationadmin@example.org', 'consultationadmin');

export const loginAsProposalAdmin = (page: Page) =>
    loginWithCredentials(page, 'proposaladmin@example.org', 'proposaladmin');

export const loginAsProgressAdmin = (page: Page) =>
    loginWithCredentials(page, 'progress@example.org', 'proposaladmin');

export const loginAsGlobalAdmin = (page: Page) =>
    loginWithCredentials(page, 'globaladmin@example.org', 'testadmin');

export const loginAsStdUser = (page: Page) =>
    loginWithCredentials(page, 'testuser@example.org', 'testuser');

export const loginAsFixedDataUser = (page: Page) =>
    loginWithCredentials(page, 'fixeddata@example.org', 'testuser');

export const loginAsFixedDataAdmin = (page: Page) =>
    loginWithCredentials(page, 'fixedadmin@example.org', 'testadmin');

export async function loginAsGruenesNetzUser(page: Page): Promise<void> {
    await page.locator('#loginLink').waitFor({ state: 'visible' });
    await page.locator('#loginLink').click();
    await page.locator('h1').filter({ hasText: /LOGIN/i }).waitFor();
    await page.locator('#gruenesNetzAccount').fill('DoeJane');
    await page.locator('#gruenesNetzLoginForm [name="gruenesNetzLogin"]').click();
    await page.locator('#logoutLink').waitFor({ state: 'visible' });
}

export async function loginAsYfjUser(
    page: Page,
    emailPrefix: string,
    userNo: number,
): Promise<void> {
    const username = `${emailPrefix}-${userNo}@example.org`;
    await loginWithCredentials(page, username, 'Test');
}

export async function logout(page: Page): Promise<void> {
    await page.locator('#logoutLink').waitFor({ state: 'visible' });
    await page.locator('#logoutLink').click();
    await page.locator('#loginLink').waitFor({ state: 'visible' });
}

export class RestAuth {
    private tokens: Map<string, string> = new Map();

    constructor(private readonly request: APIRequestContext) {}

    async login(
        username: string,
        password: string,
        subdomain: string = DEFAULT_SUBDOMAIN,
    ): Promise<string> {
        const key = `${subdomain}:${username}`;
        const cached = this.tokens.get(key);
        if (cached !== undefined) return cached;

        const response = await this.request.post(
            `/${subdomain}/rest/user/login`,
            {
                data: { username, password },
                headers: { 'Content-Type': 'application/json' },
            },
        );
        if (!response.ok()) {
            throw new Error(
                `REST login failed for ${username}: ${response.status()} ${await response.text()}`,
            );
        }
        const body = await response.json();
        const token = body.token;
        if (!token) {
            throw new Error(`REST login response missing token: ${JSON.stringify(body)}`);
        }
        this.tokens.set(key, token);
        return token;
    }

    asStdAdmin(subdomain?: string) {
        return this.login('testadmin@example.org', 'testadmin', subdomain);
    }
    asConsultationAdmin(subdomain?: string) {
        return this.login('consultationadmin@example.org', 'consultationadmin', subdomain);
    }
    asProposalAdmin(subdomain?: string) {
        return this.login('proposaladmin@example.org', 'proposaladmin', subdomain);
    }
    asProgressAdmin(subdomain?: string) {
        return this.login('progress@example.org', 'proposaladmin', subdomain);
    }
    asGlobalAdmin(subdomain?: string) {
        return this.login('globaladmin@example.org', 'testadmin', subdomain);
    }
    asStdUser(subdomain?: string) {
        return this.login('testuser@example.org', 'testuser', subdomain);
    }
    asFixedDataUser(subdomain?: string) {
        return this.login('fixeddata@example.org', 'testuser', subdomain);
    }

    bearerHeaders(token: string): Record<string, string> {
        return { Authorization: `Bearer ${token}` };
    }
}

export async function clearLocalStorage(page: Page): Promise<void> {
    await page.evaluate(() => {
        for (const key of Object.keys(localStorage)) localStorage.removeItem(key);
    });
}

export function consultationPath(
    subdomain: string = DEFAULT_SUBDOMAIN,
    path: string = DEFAULT_CONSULTATION_PATH,
): string {
    return `/${subdomain}/${path}`;
}
