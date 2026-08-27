import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class VotingAdminPage extends BasePage {
    protected route = 'consultation/admin-votings';

    get heading(): Locator {
        return this.page.locator('h1').filter({ hasText: /abstimmungen/i });
    }
}