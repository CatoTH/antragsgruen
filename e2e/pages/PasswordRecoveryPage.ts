import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class PasswordRecoveryPage extends BasePage {
    protected route = 'user/recovery';

    get heading(): Locator {
        return this.page.locator('h1').filter({ hasText: /passwort/i });
    }
}