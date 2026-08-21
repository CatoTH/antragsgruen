import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class AmendmentCreatePage extends BasePage {
    protected route = 'amendment/create';

    get heading(): Locator {
        return this.page.locator('h1').filter({ hasText: /stellen/i });
    }
}