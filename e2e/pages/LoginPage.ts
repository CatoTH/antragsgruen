import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class LoginPage extends BasePage {
    protected route = 'user/login';

    get heading(): Locator {
        return this.page.locator('h1').filter({ hasText: /LOGIN/i });
    }

    async login(username: string, password: string): Promise<void> {
        await this.heading.waitFor();
        await this.page.locator('#username').fill(username);
        await this.page.locator('#passwordInput').fill(password);
        await this.page
            .locator('#usernamePasswordForm [name="loginusernamepassword"]')
            .click();
    }
}