import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class AdminAmendmentPage extends BasePage {
    protected route = 'admin/amendment/update';

    get statusSelect(): Locator {
        return this.page.locator('#amendmentStatus');
    }
}