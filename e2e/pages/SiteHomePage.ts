import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class SiteHomePage extends BasePage {
    protected route = 'consultation/home';

    get heading(): Locator {
        return this.page.locator('h1');
    }
}