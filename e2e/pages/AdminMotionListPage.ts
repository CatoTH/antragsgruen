import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class AdminMotionListPage extends BasePage {
    protected route = 'admin/motion-list/index';

    async gotoMotionEdit(motionId: number): Promise<AdminMotionPage> {
        await this.page
            .locator(`.motion${motionId} .edit, .motion${motionId} [href*="edit"]`)
            .first()
            .click();
        return new AdminMotionPage(this.page);
    }
}

import { AdminMotionPage } from './AdminMotionPage';