import { Page, Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class MotionPage extends BasePage {
    protected route = 'motion/view';

    get dataContainer(): Locator {
        return this.page.locator('.motionData');
    }
}