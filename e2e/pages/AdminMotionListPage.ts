import { BasePage } from './BasePage';
import { AdminMotionPage } from './AdminMotionPage';
import { AdminAmendmentPage } from './AdminAmendmentPage';

export class AdminMotionListPage extends BasePage {
    protected route = 'admin/motion-list/index';

    async gotoMotionEdit(motionId: number): Promise<AdminMotionPage> {
        await this.page
            .locator(`.adminMotionTable .motion${motionId} .titleCol a`)
            .click();
        return new AdminMotionPage(this.page);
    }

    async gotoAmendmentEdit(amendmentId: number): Promise<AdminAmendmentPage> {
        await this.page
            .locator(`.adminMotionTable .amendment${amendmentId} .titleCol a`)
            .click();
        return new AdminAmendmentPage(this.page);
    }
}
