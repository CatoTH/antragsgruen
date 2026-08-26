import { test, expect } from '../../fixtures';
import { AmendmentPage } from '../../pages/AmendmentPage';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_COMMENT_ID } from '../../utils/constants';
import { acceptBootbox, dispatchClick } from '../../utils/dom';
import { expectFeedContains } from '../../utils/feeds';
import { gotoConsultationHome } from '../../utils/navigation';

test.describe('Amendments: CommentWrite', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('write a comment, but forgot my name', async ({ page }) => {
        await test.step('write a comment, but forgot my name', async () => {
            await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });

            await expect(page.locator('section.comments')).toContainText('Kommentar schreiben');
            await page.locator('#comment_-1_-1_name').first().fill('');
            await page.locator('#comment_-1_-1_email').first().fill('test@example.org');
            await page.locator('#comment_-1_-1_text').first().fill('Some Text');
            await page.evaluate(() => {
                const w = window as any;
                w.$('[required]').removeAttr('required');
            });
            await page.locator('section.comments .commentForm [name="writeComment"]').click();

            await expect(page.locator('body')).toContainText('Bitte gib deinen Namen an');
            await expect(page.locator('section.comments')).toContainText('Kommentar schreiben');
            await expect(page.locator('#comment_-1_-1_name')).toHaveValue('');
            await expect(page.locator('#comment_-1_-1_email')).toHaveValue('test@example.org');
            await expect(page.locator('#comment_-1_-1_text')).toHaveValue('Some Text');
        });

        await test.step('enter the missing data and submit', async () => {
            await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
            await page.locator('#comment_-1_-1_name').first().fill('My Name');
            await page.locator('section.comments .commentForm [name="writeComment"]').click();

            await expect(page.locator('section.comments .motionComment')).toContainText('My Name');
            await expect(page.locator('section.comments .motionComment')).toContainText('Some Text');
            await expect(
                page.locator('section.comments .motionComment .delLink'),
            ).not.toBeAttached();
        });

        await test.step('write a reply to this comment', async () => {
            await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
            await expect(page.locator('.replyComment').filter({ visible: true })).toHaveCount(0);
            await expect(
                page.locator(`#comment_-1_-1_${FIRST_FREE_COMMENT_ID}_text`),
            ).not.toBeVisible();
            await page.locator(`#comment${FIRST_FREE_COMMENT_ID} .replyButton`).click();
            await expect(page.locator('.replyComment').first()).toBeVisible();
            await expect(
                page.locator(`#comment_-1_-1_${FIRST_FREE_COMMENT_ID}_text`),
            ).toBeVisible();
            await page
                .locator(`#comment_-1_-1_${FIRST_FREE_COMMENT_ID}_name`)
                .fill('My Name 2');
            await page
                .locator(`#comment_-1_-1_${FIRST_FREE_COMMENT_ID}_email`)
                .fill('reply@example.org');
            await page
                .locator(`#comment_-1_-1_${FIRST_FREE_COMMENT_ID}_text`)
                .fill('This is a reply');
            await page
                .locator(`#comment_-1_-1_${FIRST_FREE_COMMENT_ID}_form [name="writeComment"]`)
                .click();

            await expect(page.locator('.motionCommentReplies .motionComment')).toContainText(/My Name 2/i);
            await expect(page.locator('.motionCommentReplies .motionComment')).toContainText(
                'This is a reply',
            );
            await expect(page.locator('.motionComment .delLink')).not.toBeAttached();

            await gotoConsultationHome(page);
            await page.locator('#sidebar .feeds a').click();
            await expectFeedContains(page, '.feedAll', ['My Name']);
        });

        await test.step('see the comment on the sidebar and the feed', async () => {
            await gotoConsultationHome(page);
            await expect(page.locator('#sidebar .comments')).toContainText('My Name');
            await page.locator('#sidebar .feeds a').click();
            await expectFeedContains(page, '.feedComments', ['My Name']);
        });

        await test.step('disable comments for this specific amendment and delete the comment', async () => {
            await page.locator('#motionListLink').click();
            await page
                .locator('.adminMotionTable .amendment1 .titleCol a')
                .first()
                .click();
            await page.locator('.preventFunctionality .notCommentable input').first().check();
            await page.locator('#amendmentUpdateForm [name="save"]').click();

            await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
            await expect(page.locator('.commentsDeactivatedHint').first()).toBeVisible();
            await expect(page.locator('#comment_-1_-1_text').filter({ visible: true })).toHaveCount(0);

            await expect(page.locator('#commentsTitle').first()).toBeVisible();
            await dispatchClick(page, 'section.comments #comment1 .delLink button');
            await acceptBootbox(page);

            await expect(page.locator('section.comments .motionComment').getByText('My Name').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('section.comments .motionComment').getByText('Some Text').filter({ visible: true })).toHaveCount(0);
        });
    });
});