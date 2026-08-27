import { Locator } from '@playwright/test';
import { BasePage } from './BasePage';

export class MotionPage extends BasePage {
    protected route = 'motion/view';

    get dataContainer(): Locator {
        return this.page.locator('.motionData');
    }

    async getFirstLineNumber(): Promise<number> {
        return this.page.evaluate(
            () => (window as any).$('.motionTextHolder .paragraph .lineNumber').first().data('line-number') as number,
        );
    }
}
