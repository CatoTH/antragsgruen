import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { expectBootboxDialog, acceptBootbox } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/BasePage';
import { MotionPage } from '../../pages/MotionPage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';

async function writeComment(page: import('@playwright/test').Page): Promise<void> {
    const home = new ConsultationHomePage(page);
    await home.open();
    await home.gotoMotionView(2);

    await expect(page.locator('section.comments')).toContainText('Kommentar schreiben');
    await page.locator('#comment_-1_-1_name').fill('My Name');
    await page.locator('#comment_-1_-1_email').fill('test@example.org');
    await page.locator('#comment_-1_-1_text').fill('Some Text');
    await page.locator('section.comments .commentForm [name="writeComment"]').click();
}

test.describe('Whole-motion comments', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('missing name is rejected and the form keeps its values', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionView(2);

        await expect(page.locator('section.comments')).toContainText('Kommentar schreiben');
        await expect(page.locator('#comment_-1_-1_text')).toBeVisible();

        await page.locator('#comment_-1_-1_name').evaluate((el) => el.removeAttribute('required'));
        await page.locator('#comment_-1_-1_name').fill('');
        await page.locator('#comment_-1_-1_email').fill('test@example.org');
        await page.locator('#comment_-1_-1_text').fill('Some Text');
        await page.locator('section.comments .commentForm [name="writeComment"]').click();

        await expect(page.locator('body')).toContainText('Bitte gib deinen Namen an');
        await expect(page.locator('section.comments')).toContainText('Kommentar schreiben');
        await expect(page.locator('#comment_-1_-1_name')).toHaveValue('');
        await expect(page.locator('#comment_-1_-1_email')).toHaveValue('test@example.org');
        await expect(page.locator('#comment_-1_-1_text')).toHaveValue('Some Text');
    });

    test('a valid comment is shown on the motion', async ({ page }) => {
        await writeComment(page);

        await expect(page.locator('section.comments .motionComment')).toContainText(/my name/i);
        await expect(page.locator('section.comments .motionComment')).toContainText('Some Text');
        await expect(page.locator('section.comments .motionComment .delLink')).toHaveCount(0);
    });

    test('the comment shows up in the sidebar and the feeds', async ({ page }) => {
        await writeComment(page);

        const home = new ConsultationHomePage(page);
        await home.open();
        await expect(page.locator('#sidebar .comments')).toContainText('My Name');
        await page.locator('#sidebar .feeds a').click();
        await page.locator('.feedComments').click();
        await expect(page.locator('body')).toContainText('My Name');

        await home.open();
        await page.locator('#sidebar .feeds a').click();
        await page.locator('.feedAll').click();
        await expect(page.locator('body')).toContainText('My Name');
    });

    test('comments can be disabled for a specific motion', async ({ page }) => {
        await writeComment(page);

        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const motionList = new AdminMotionListPage(page);
        await motionList.open();
        await motionList.gotoMotionEdit(2);
        await page.locator('.preventFunctionality .notCommentable input').check();
        await page.locator('#motionUpdateForm [name="save"]').click();

        const motion = new MotionPage(page);
        await motion.open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.commentsDeactivatedHint')).toBeVisible();
        await expect(page.locator('#comment_-1_-1_text')).toHaveCount(0);
    });

    test('an admin can delete the comment', async ({ page }) => {
        await writeComment(page);

        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);
        await home.open();
        await home.gotoMotionView(2);

        await expect(page.locator('#commentsTitle')).toBeVisible();
        await expect(page.locator('section.comments .motionComment .delLink')).toBeAttached();

        await page.locator('section.comments #comment1 .delLink button').click();
        await expectBootboxDialog(page, /Wirklich löschen/);
        await acceptBootbox(page);

        await expect(page.locator('section.comments .motionComment')).not.toContainText('My Name');
        await expect(page.locator('section.comments .motionComment')).not.toContainText('Some Text');
    });
});
