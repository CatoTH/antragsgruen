import { expect } from '@playwright/test';
import { BasePage } from './BasePage';
import { MotionCreatePage } from './MotionCreatePage';
import { MotionPage } from './MotionPage';
import { AmendmentCreatePage } from './AmendmentCreatePage';
import { AmendmentPage } from './AmendmentPage';
import {
    DEFAULT_CONSULTATION_PATH,
    DEFAULT_SUBDOMAIN,
} from '../utils/constants';

export class ConsultationHomePage extends BasePage {
    protected route = 'consultation/index';

    static SUBDOMAIN = DEFAULT_SUBDOMAIN;
    static PATH = DEFAULT_CONSULTATION_PATH;

    async gotoMotionCreatePage(
        motionTypeId: number = 1,
        check: boolean = true,
        subdomain: string = DEFAULT_SUBDOMAIN,
        path: string = DEFAULT_CONSULTATION_PATH,
    ): Promise<MotionCreatePage> {
        const page = new MotionCreatePage(this.page);
        await page.open({ subdomain, consultationPath: path, motionTypeId });
        if (check) {
            await expect(page.heading).toBeVisible();
        }
        return page;
    }

    async gotoAmendmentCreatePage(
        motionSlug: string = '321-o-zapft-is',
        check: boolean = true,
    ): Promise<AmendmentCreatePage> {
        const page = new AmendmentCreatePage(this.page);
        await page.open({ motionSlug });
        if (check) {
            await expect(page.heading).toBeVisible();
        }
        return page;
    }

    async gotoMotionView(motionId: number): Promise<MotionPage> {
        await this.page.locator(`.motionLink${motionId}`).click();
        await this.page.locator('.motionData').waitFor();
        return new MotionPage(this.page);
    }

    async gotoAmendmentView(amendmentId: number): Promise<AmendmentPage> {
        await this.page.locator(`.amendment${amendmentId}`).click();
        await this.page.locator('.motionData').waitFor();
        return new AmendmentPage(this.page);
    }
}
