import { Page } from '@playwright/test';
import { BasePage } from './BasePage';

export class AdminConsultationPage extends BasePage {
    protected route = 'admin/index/consultation';

    static MAINTENANCE_CHECKBOX = '#maintenanceMode';

    async selectAmendmentNumbering(numbering: string): Promise<void> {
        await this.page.locator('#amendmentNumbering').selectOption(numbering);
    }

    async saveForm(): Promise<void> {
        await this.page.locator('#consultationSettingsForm [name="save"]').first().click();
    }
}