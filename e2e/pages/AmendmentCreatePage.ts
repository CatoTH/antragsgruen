import { expect, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class AmendmentCreatePage extends BasePage {
    protected route = 'amendment/create';

    get heading(): Locator {
        return this.page.locator('h1').filter({ hasText: /stellen/i });
    }

    async createAmendment(title: string, isPublishedImmediately: boolean): Promise<void> {
        await this.fillInValidSampleData(title);
        await this.saveForm();
        await expect(
            this.page.locator('h1').filter({ hasText: /antrag bestätigen/i }),
        ).toBeVisible();
        await this.page.locator('#amendmentConfirmForm [name="confirm"]').first().click();
        if (isPublishedImmediately) {
            await expect(
                this.page.locator('h1').filter({ hasText: /änderungsantrag veröffentlicht/i }),
            ).toBeVisible();
        } else {
            await expect(
                this.page.locator('h1').filter({ hasText: /antrag eingereicht/i }),
            ).toBeVisible();
        }
    }

    async fillInValidSampleData(title: string = 'Neuer Testantrag 1'): Promise<void> {
        await this.page.locator('#initiatorPrimaryName').fill('Mein Name');
        await this.page.locator('#initiatorEmail').fill('test@example.org');
        await this.page.locator('#sections_1').fill(title);
    }

    async saveForm(): Promise<void> {
        await this.page.locator('#amendmentEditForm [name="save"]').first().click();
    }
}
