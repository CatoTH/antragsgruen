import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class AdminIndexPage extends BasePage {
    protected route = 'admin/index';

    get motionListLink(): Locator {
        return this.page.locator('#motionListLink');
    }

    async gotoMotionList(): Promise<AdminMotionListPage> {
        await this.motionListLink.click();
        await this.page
            .locator('h1')
            .filter({ hasText: /liste: anträge/i })
            .waitFor();
        return new AdminMotionListPage(this.page);
    }
}

import { AdminMotionListPage } from './AdminMotionListPage';