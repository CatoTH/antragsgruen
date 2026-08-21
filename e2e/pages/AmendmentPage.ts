import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class AmendmentPage extends BasePage {
    protected route = 'amendment/view';

    get dataContainer(): Locator {
        return this.page.locator('.motionData');
    }
}