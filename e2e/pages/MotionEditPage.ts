import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class MotionEditPage extends BasePage {
    protected route = 'motion/edit';

    get statusSelect(): Locator {
        return this.page.locator('#motionStatus');
    }

    get titleField(): Locator {
        return this.page.locator('#motionTitle');
    }

    get titlePrefixField(): Locator {
        return this.page.locator('#motionTitlePrefix');
    }
}