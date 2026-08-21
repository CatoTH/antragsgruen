import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class ManagerStartPage extends BasePage {
    protected route: string[] = ['/antragsgruen_sites/manager/index'];

    get heading(): Locator {
        return this.page.locator('h1');
    }
}