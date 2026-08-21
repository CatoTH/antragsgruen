import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class ContentPage extends BasePage {
    protected route = 'pages/show-page';

    get heading(): Locator {
        return this.page.locator('h1');
    }
}