import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { expectBootboxDialog, acceptBootbox } from '../../utils/dom';
import { FIRST_FREE_COMMENT_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

const POLICY_ALL = '2';

async function allowCommentsForEveryone(page: import('@playwright/test').Page): Promise<void> {
    const home = new ConsultationHomePage(page);
    await home.open({ subdomain: 'bdk', consultationPath: 'bdk' });
    await loginAsStdAdmin(page);

    const motionType = new AdminMotionTypePage(page);
    await motionType.open({ subdomain: 'bdk', consultationPath: 'bdk', motionTypeId: 7 });
    await page.locator('#typePolicyComments').first().selectOption(POLICY_ALL);
    await page.locator('.adminTypeForm [name="save"]').first().click();
    await logout(page);
}

async function openMotionWithComments(page: import('@playwright/test').Page): Promise<void> {
    const home = new ConsultationHomePage(page);
    await home.open({ subdomain: 'bdk', consultationPath: 'bdk' });
    await home.gotoMotionView(4);
}

test.describe('Paragraph comments', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('missing name is rejected and the form keeps its values', async ({ page }) => {
        await test.step('missing name is rejected and the form keeps its values', async () => {
            await allowCommentsForEveryone(page);
            await openMotionWithComments(page);

            await test.step('allow comments for everyone', async () => {
                await expect(page.locator('body')).not.toContainText('Kommentar schreiben', { useInnerText: true });
                await page.locator('#section_21_1 .comment .shower').click();
            });

            await test.step('write a comment, but forget my name', async () => {
                await expect(page.locator('#section_21_1')).toContainText('Kommentar schreiben');
            });

            await test.step('enter the missing data', async () => {
                await page.locator('#comment_21_1_name').evaluate((el) => el.removeAttribute('required'));
                await page.locator('#comment_21_1_name').first().fill('');
                await page.locator('#comment_21_1_email').first().fill('test@example.org');
                await page.locator('#comment_21_1_text').first().fill('Some Text');
                await page.locator('#section_21_1 .commentForm [name="writeComment"]').click();

                await expect(page.locator('body')).toContainText('Bitte gib deinen Namen an');
                await expect(page.locator('#section_21_1')).toContainText('Kommentar schreiben');
                await expect(page.locator('#comment_21_1_name')).toHaveValue('');
                await expect(page.locator('#comment_21_1_email')).toHaveValue('test@example.org');
                await expect(page.locator('#comment_21_1_text')).toHaveValue('Some Text');
            });
        });

        await test.step('a valid comment can be written and replied to', async () => {
            await allowCommentsForEveryone(page);
            await openMotionWithComments(page);

            await page.locator('#section_21_1 .comment .shower').click();
            await page.locator('#comment_21_1_name').first().fill('My Name');
            await page.locator('#comment_21_1_email').first().fill('test@example.org');
            await page.locator('#comment_21_1_text').first().fill('Some Text');
            await page.locator('#section_21_1 .commentForm [name="writeComment"]').click();

            await expect(page.locator('#section_21_1 .motionComment')).toContainText(/my name/i);
            await expect(page.locator('#section_21_1 .motionComment')).toContainText('Some Text');
            await expect(page.locator('#section_21_1 .motionComment .delLink')).toHaveCount(0);

            await test.step('write a reply to this comment', async () => {
                await expect(page.locator('.replyComment').filter({ visible: true })).toHaveCount(0);
                await expect(page.locator(`#comment_21_1_${FIRST_FREE_COMMENT_ID}_text`).filter({ visible: true })).toHaveCount(0);
                await page.locator(`#comment${FIRST_FREE_COMMENT_ID} .replyButton`).click();
                await expect(page.locator('.replyComment').first()).toBeVisible();
            });

            await test.step('see the comment on the sidebar and the feed', async () => {
                await page.locator(`#comment_21_1_${FIRST_FREE_COMMENT_ID}_name`).first().fill('My Name 2');
                await page.locator(`#comment_21_1_${FIRST_FREE_COMMENT_ID}_email`).first().fill('reply@example.org');
                await page.locator(`#comment_21_1_${FIRST_FREE_COMMENT_ID}_text`).first().fill('This is a reply');
                await page
                    .locator(`#comment_21_1_${FIRST_FREE_COMMENT_ID}_form [name="writeComment"]`)
                    .click();

                const replies = page.locator('#section_21_1 .motionCommentReplies .motionComment');
                await expect(replies).toContainText(/my name 2/i);
                await expect(replies).toContainText('This is a reply');
                await expect(page.locator('#section_21_1 .motionComment .delLink')).toHaveCount(0);
            });
        });

        await test.step('the comment shows up in the sidebar and the feeds', async () => {
            await allowCommentsForEveryone(page);
            await openMotionWithComments(page);

            await page.locator('#section_21_1 .comment .shower').click();
            await page.locator('#comment_21_1_name').first().fill('My Name');
            await page.locator('#comment_21_1_email').first().fill('test@example.org');
            await page.locator('#comment_21_1_text').first().fill('Some Text');
            await page.locator('#section_21_1 .commentForm [name="writeComment"]').click();

            const home = new ConsultationHomePage(page);
            await home.open({ subdomain: 'bdk', consultationPath: 'bdk' });
            await expect(page.locator('#sidebar .comments')).toContainText('My Name');

            await page.locator('#sidebar .feeds a').click();
            await page.locator('.feedComments').click();
            await expect(page.locator('body')).toContainText('My Name');

            await home.open({ subdomain: 'bdk', consultationPath: 'bdk' });
            await page.locator('#sidebar .feeds a').click();
            await page.locator('.feedAll').click();
            await expect(page.locator('body')).toContainText('My Name');
        });

        await test.step('an admin can delete the comment', async () => {
            await allowCommentsForEveryone(page);
            await openMotionWithComments(page);

            await page.locator('#section_21_1 .comment .shower').click();
            await page.locator('#comment_21_1_name').first().fill('My Name');
            await page.locator('#comment_21_1_email').first().fill('test@example.org');
            await page.locator('#comment_21_1_text').first().fill('Some Text');
            await page.locator('#section_21_1 .commentForm [name="writeComment"]').click();

            const home = new ConsultationHomePage(page);
            await home.open({ subdomain: 'bdk', consultationPath: 'bdk' });
            await loginAsStdAdmin(page);
            await home.open({ subdomain: 'bdk', consultationPath: 'bdk' });
            await home.gotoMotionView(4);

            await page.locator('#section_21_1 .comment .shower').click();
            await expect(page.locator('#section_21_1')).toContainText('Kommentar schreiben');
            await expect(page.locator('#section_21_1 .motionComment .delLink')).toBeAttached();

            await page.locator('#section_21_1 #comment1 .delLink button').click();
            await expectBootboxDialog(page, /Wirklich löschen/);
            await acceptBootbox(page);

            await page.locator('#section_21_1 .comment .shower').click();
            await expect(page.locator('#section_21_1 .motionComment').getByText('My Name').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#section_21_1 .motionComment').getByText('Some Text').filter({ visible: true })).toHaveCount(0);
        });
    });
});
