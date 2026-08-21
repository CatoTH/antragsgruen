import { Page, expect } from '@playwright/test';
import {
    DEFAULT_CONSULTATION_PATH,
    DEFAULT_SUBDOMAIN,
} from '../utils/constants';

export interface UrlResponse {
    url: string;
    ok: boolean;
    error?: string;
}

export abstract class BasePage {
    protected abstract route: string | string[];

    constructor(protected readonly page: Page) {}

    async getUrl(params: Record<string, any> = {}): Promise<string> {
        const response = await this.page.request.post('/test/url-builder', {
            form: {
                route: this.route as string,
                params: JSON.stringify(params),
            },
        });
        if (!response.ok()) {
            throw new Error(
                `URL builder failed: ${response.status()} ${await response.text()}`,
            );
        }
        const data: UrlResponse = await response.json();
        if (!data.ok) {
            throw new Error(`URL builder error: ${data.error}`);
        }
        return data.url;
    }

    async open(params: Record<string, any> = {}): Promise<void> {
        const url = await this.getUrl(params);
        await this.page.goto(url);
    }
}

import { MotionCreatePage } from './MotionCreatePage';
import { MotionPage } from './MotionPage';
import { AmendmentCreatePage } from './AmendmentCreatePage';
import { AmendmentPage } from './AmendmentPage';

export class ConsultationHomePage extends BasePage {
    protected route = 'consultation/index';

    static SUBDOMAIN = DEFAULT_SUBDOMAIN;
    static PATH = DEFAULT_CONSULTATION_PATH;

    async gotoMotionCreatePage(
        motionTypeId: number = 1,
    ): Promise<MotionCreatePage> {
        const page = new MotionCreatePage(this.page);
        await page.open({ motionTypeId });
        await expect(page.heading).toContainText(/antrag stellen/i);
        return page;
    }

    async gotoAmendmentCreatePage(
        motionSlug: string = '321-o-zapft-is',
    ): Promise<AmendmentCreatePage> {
        const page = new AmendmentCreatePage(this.page);
        await page.open({ motionSlug });
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