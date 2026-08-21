import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class AmendmentEditPage extends BasePage {
    protected route = 'amendment/edit';

    get statusSelect(): Locator {
        return this.page.locator('#amendmentStatus');
    }
}