import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class VotingResultsPage extends BasePage {
    protected route = 'consultation/voting-results';

    get heading(): Locator {
        return this.page.locator('h1').filter({ hasText: /abstimmungsergebnisse/i });
    }
}