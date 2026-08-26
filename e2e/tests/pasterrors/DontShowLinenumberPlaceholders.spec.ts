import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { MotionPage } from '../../pages/MotionPage';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('DontShowLinenumberPlaceholders', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('disable line numbers and verify placeholders are not shown', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);

        const motionTypePage = new AdminMotionTypePage(page);
        await new AdminIndexPage(page).open();
        await motionTypePage.open({ motionTypeId: 1 });

        await expect(page.locator('.section2 .lineNumbers')).toBeChecked();
        await page.locator('.section2 .lineNumbers').first().uncheck();
        await motionTypePage.saveForm();
        await expect(page.locator('.section2 .lineNumbers')).not.toBeChecked();

        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('body')).not.toContainText('###LINENUMBER###', { useInnerText: true });
    });
});