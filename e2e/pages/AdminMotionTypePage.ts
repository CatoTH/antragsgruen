import { Page } from '@playwright/test';
import { BasePage } from './BasePage';

export class AdminMotionTypePage extends BasePage {
    protected route = 'admin/motion-type/type';

    static TABULAR_LABEL = 'Angaben';
    static COMMENTS_LABEL = 'Kommentare';

    async getCurrentOrder(): Promise<number[]> {
        return this.page.evaluate(() => {
            const w = window as any;
            return w.$('#sectionsList').data('sortable').toArray();
        });
    }

    async setCurrentOrder(order: number[]): Promise<void> {
        await this.page.evaluate((o) => {
            const w = window as any;
            w.$('#sectionsList').data('sortable').sort(o);
        }, order);
    }

    async saveForm(): Promise<void> {
        await this.page.locator('.adminTypeForm [name="save"]').click();
    }
}