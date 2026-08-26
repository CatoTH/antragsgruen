import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('EditingMotionsAfterParagraphComments', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable paragraph-based comments, write a comment, edit the motion', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);

        const motionTypePage = new AdminMotionTypePage(page);
        await new AdminIndexPage(page).open();
        await motionTypePage.open({ motionTypeId: 1 });
        await page.locator('.section2 .commentParagraph input').first().check();
        await motionTypePage.saveForm();

        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionView(2);

        await page.waitForTimeout(1000);
        await page.locator('#section_2_5 .comment .shower').scrollIntoViewIfNeeded();
        await page.locator('#section_2_5 .comment .shower').click();
        await expect(page.locator('#section_2_5 .commentForm').first()).toBeVisible();
        await page.locator('#comment_2_5_text').first().fill("My test'\n\\My test 2");
        await page.locator('#section_2_5 .commentForm [name="writeComment"]').click();

        await test.step('see the comment', async () => {
            await expect(page.locator('#section_2_5 .motionComment')).toContainText('Testadmin');
            await expect(page.locator('#section_2_5 .motionComment')).toContainText("My test'\n\\My test 2");

            await page.locator('#sidebar .adminEdit a').click();
            await page.locator('#motionTitle').first().fill('My new title');
            await page.locator('#motionUpdateForm [name="save"]').click();
            await new ConsultationHomePage(page).open();
            await expect(page.locator('body')).toContainText('My new title');
        });
    });
});