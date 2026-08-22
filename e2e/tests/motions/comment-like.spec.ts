import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';
import { MotionPage } from '../../pages/MotionPage';

test.describe('Comment likes', () => {
    test.skip(true, 'Disabled in the original Codeception suite');

    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('write a comment, enable ratings and support it', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: '321-o-zapft-is' });

        await expect(page.locator('body')).not.toContainText('Kommentar schreiben');
        await page.locator('#section_3_1 .comment .shower').click();
        await expect(page.locator('#section_3_1')).toContainText('Kommentar schreiben');
        await page.locator('#comment_3_1_name').fill('My name');
        await page.locator('#comment_3_1_email').fill('test@example.org');
        await page.locator('#comment_3_1_text').fill('Some Text');
        await page.locator('#section_3_1 .commentForm [name="writeComment"]').click();
        await expect(page.locator('#section_3_1 .motionComment')).toContainText('My Name');
        await expect(page.locator('#section_3_1 .motionComment')).toContainText('Some Text');
        await expect(page.locator('.commentSupporterHolder')).toHaveCount(0);

        await loginAsStdAdmin(page);
        const consultation = new AdminConsultationPage(page);
        await consultation.open();
        await page.locator('#commentsSupportable').check();
        await consultation.saveForm();

        await logout(page);
        await motion.open({ motionSlug: '321-o-zapft-is' });
        await page.locator('#section_3_1 .comment .shower').click();
        await expect(page.locator('#section_3_1 .motionComment')).toContainText('My Name');
        await expect(page.locator('.commentSupporterHolder')).toBeVisible();
        await page.locator('.commentSupporterHolder [name="commentLike"]').click();
    });
});
