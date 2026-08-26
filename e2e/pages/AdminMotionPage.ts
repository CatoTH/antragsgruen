import { Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class AdminMotionPage extends BasePage {
    protected route = 'admin/motion/update';

    get statusSelect(): Locator {
        return this.page.locator('#motionStatus');
    }

    get titleField(): Locator {
        return this.page.locator('#motionTitle');
    }

    get titlePrefixField(): Locator {
        return this.page.locator('#motionTitlePrefix');
    }

    async saveForm(): Promise<void> {
        await this.page.locator('#motionUpdateForm [name="save"]').first().click();
    }
}
