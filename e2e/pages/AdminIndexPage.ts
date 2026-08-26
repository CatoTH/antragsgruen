import { expect, Locator } from '@playwright/test';
import { BasePage } from './BasePage';
import { AdminMotionListPage } from './AdminMotionListPage';
import { AdminMotionTypePage } from './AdminMotionTypePage';
import { AdminConsultationPage } from './AdminConsultationPage';
import { AdminAppearancePage } from './AdminAppearancePage';
import { VotingAdminPage } from './VotingAdminPage';

export class AdminIndexPage extends BasePage {
    protected route = 'admin/index';

    get motionListLink(): Locator {
        return this.page.locator('#motionListLink');
    }

    async gotoMotionList(): Promise<AdminMotionListPage> {
        await this.motionListLink.click();
        await expect(
            this.page.locator('h1').filter({ hasText: /liste: anträge/i }),
        ).toBeVisible();
        return new AdminMotionListPage(this.page);
    }

    async gotoMotionTypes(motionTypeId: number): Promise<AdminMotionTypePage> {
        await this.page.locator(`.motionType${motionTypeId}`).click();
        await expect(
            this.page.locator('h1').filter({ hasText: /antragstyp bearbeiten/i }),
        ).toBeVisible();
        return new AdminMotionTypePage(this.page);
    }

    async gotoConsultation(): Promise<AdminConsultationPage> {
        await this.page.locator('#consultationLink').click();
        return new AdminConsultationPage(this.page);
    }

    async gotoAppearance(): Promise<AdminAppearancePage> {
        await this.page.locator('#appearanceLink').click();
        return new AdminAppearancePage(this.page);
    }

    async gotoUserAdministration(): Promise<void> {
        await this.page.locator('.siteUsers').click();
    }

    async gotoConsultationCreatePage(): Promise<void> {
        await this.page.locator('.siteConsultationsLink').click();
    }

    async gotoVotingPage(): Promise<VotingAdminPage> {
        await this.page.locator('.votingAdminLink').click();
        return new VotingAdminPage(this.page);
    }
}
