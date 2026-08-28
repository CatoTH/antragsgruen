import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class EmailChangePage extends BasePage {
    protected route = 'user/emailchange';

    get heading(): Locator {
        return this.page.locator('h1').filter({ hasText: /e-?mail/i });
    }
}