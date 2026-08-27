import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { acceptBootbox, dispatchClick, expectBootboxDialog } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { MotionPage } from '../../pages/MotionPage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';
import { expectFeedContains } from '../../utils/feeds';

async function writeComment(page: import('@playwright/test').Page): Promise<void> {
    const home = new ConsultationHomePage(page);
    await home.open();
    await home.gotoMotionView(2);

    await expect(page.locator('section.comments')).toContainText('Kommentar schreiben');
    await page.locator('#comment_-1_-1_name').first().fill('My Name');
    await page.locator('#comment_-1_-1_email').first().fill('test@example.org');
    await page.locator('#comment_-1_-1_text').first().fill('Some Text');
    await page.locator('section.comments .commentForm [name="writeComment"]').click();
}

test.describe('Whole-motion comments', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('missing name is rejected and the form keeps its values', async ({ page }) => {
        await test.step('missing name is rejected and the form keeps its values', async () => {
            const home = new ConsultationHomePage(page);
            await home.open();
            await home.gotoMotionView(2);

            await test.step('write a comment, but forget my name', async () => {
                await expect(page.locator('section.comments')).toContainText('Kommentar schreiben');
                await expect(page.locator('#comment_-1_-1_text').first()).toBeVisible();
            });

            await test.step('enter the missing data', async () => {
                await page.locator('#comment_-1_-1_name').evaluate((el) => el.removeAttribute('required'));
                await page.locator('#comment_-1_-1_name').first().fill('');
                await page.locator('#comment_-1_-1_email').first().fill('test@example.org');
                await page.locator('#comment_-1_-1_text').first().fill('Some Text');
                await page.locator('section.comments .commentForm [name="writeComment"]').click();

                await expect(page.locator('body')).toContainText('Bitte gib deinen Namen an');
                await expect(page.locator('section.comments')).toContainText('Kommentar schreiben');
                await expect(page.locator('#comment_-1_-1_name')).toHaveValue('');
                await expect(page.locator('#comment_-1_-1_email')).toHaveValue('test@example.org');
                await expect(page.locator('#comment_-1_-1_text')).toHaveValue('Some Text');
            });
        });

        await test.step('a valid comment is shown on the motion', async () => {
            await writeComment(page);

            await expect(page.locator('section.comments .motionComment')).toContainText(/my name/i);
            await expect(page.locator('section.comments .motionComment')).toContainText('Some Text');
            await expect(page.locator('section.comments .motionComment .delLink')).toHaveCount(0);
        });

        await test.step('the comment shows up in the sidebar and the feeds', async () => {
            await writeComment(page);

            const home = new ConsultationHomePage(page);
            await home.open();
            await test.step('see the comment on the sidebar and the feed', async () => {
                await expect(page.locator('#sidebar .comments')).toContainText('My Name');
                await page.locator('#sidebar .feeds a').click();
                await expectFeedContains(page, '.feedComments', ['My Name']);

                await home.open();
                await page.locator('#sidebar .feeds a').click();
                await expectFeedContains(page, '.feedAll', ['My Name']);
            });
        });

        await test.step('comments can be disabled for a specific motion', async () => {
            await writeComment(page);

            const home = new ConsultationHomePage(page);
            await home.open();
            await loginAsStdAdmin(page);

            const motionList = new AdminMotionListPage(page);
            await motionList.open();
            await page.locator('.adminMotionTable .motion2 .titleCol a').click();
            await test.step('disable comments for this specific motion', async () => {
                await page.locator('.preventFunctionality .notCommentable input').first().check();
                await page.locator('#motionUpdateForm [name="save"]').click();

                const motion = new MotionPage(page);
                await motion.open({ motionSlug: '321-o-zapft-is' });
                await expect(page.locator('.commentsDeactivatedHint').first()).toBeVisible();
                await expect(page.locator('#comment_-1_-1_text').filter({ visible: true })).toHaveCount(0);
            });
        });

        await test.step('an admin can delete the comment', async () => {
            await writeComment(page);

            const home = new ConsultationHomePage(page);
            await home.open();
            await loginAsStdAdmin(page);
            await home.open();
            await home.gotoMotionView(2);

            await expect(page.locator('#commentsTitle').first()).toBeVisible();
            await expect(page.locator('section.comments .motionComment .delLink')).toBeAttached();

            await dispatchClick(page, 'section.comments #comment1 .delLink button');
            await expectBootboxDialog(page, /Wirklich löschen/);
            await acceptBootbox(page);

            await expect(page.locator('section.comments .motionComment').getByText('My Name').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('section.comments .motionComment').getByText('Some Text').filter({ visible: true })).toHaveCount(0);
        });
    });
});
