import { Locator } from '@playwright/test';
import { BasePage } from './BasePage';
import { DEFAULT_CONSULTATION_PATH, DEFAULT_SUBDOMAIN } from '../utils/constants';

export class AmendmentPage extends BasePage {
    protected route = 'amendment/view';

    get dataContainer(): Locator {
        return this.page.locator('.motionData');
    }

    async gotoAmendmentPage(
        subdomain: string,
        consultationPath: string,
        motionSlug: string,
        amendmentId: number,
    ): Promise<AmendmentPage> {
        await this.open({
            subdomain: subdomain || DEFAULT_SUBDOMAIN,
            consultationPath: consultationPath || DEFAULT_CONSULTATION_PATH,
            motionSlug,
            amendmentId,
        });
        return this;
    }
}
