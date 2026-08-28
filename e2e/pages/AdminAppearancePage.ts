import { Page } from '@playwright/test';
import { BasePage } from './BasePage';

export class AdminAppearancePage extends BasePage {
    protected route = 'admin/index/appearance';

    async saveForm(): Promise<void> {
        await this.page.locator('#consultationAppearanceForm [name="save"]').click();
    }
}