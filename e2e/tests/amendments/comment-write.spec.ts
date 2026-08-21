import { test, expect } from '../../fixtures';
import { AmendmentPage } from '../../pages/AmendmentPage';
import {ConsultationHomePage} from '../../pages/BasePage';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_COMMENT_ID } from '../../utils/constants';
import { acceptBootbox } from '../../utils/dom';

test.describe('Amendments: CommentWrite', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('write a comment, but forgot my name', async ({ page }) => {
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is' });

        await expect(page.locator('section.comments')).toContainText('Kommentar schreiben');
        await page.locator('#comment_-1_-1_name').fill('');
        await page.locator('#comment_-1_-1_email').fill('test@example.org');
        await page.locator('#comment_-1_-1_text').fill('Some Text');
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

    test('enter the missing data and submit', async ({ page }) => {
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is' });
        await page.locator('#comment_-1_-1_name').fill('My Name');
        await page.locator('section.comments .commentForm [name="writeComment"]').click();

        await expect(page.locator('section.comments .motionComment')).toContainText('My Name');
        await expect(page.locator('section.comments .motionComment')).toContainText('Some Text');
        await expect(
            page.locator('section.comments .motionComment .delLink'),
        ).not.toBeAttached();
    });

    test('write a reply to this comment', async ({ page }) => {
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.replyComment')).not.toBeVisible();
        await expect(
            page.locator(`#comment_-1_-1_${FIRST_FREE_COMMENT_ID}_text`),
        ).not.toBeVisible();
        await page.locator(`#comment${FIRST_FREE_COMMENT_ID} .replyButton`).click();
        await expect(page.locator('.replyComment')).toBeVisible();
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

        await expect(page.locator('.motionCommentReplies .motionComment')).toContainText('MY NAME 2');
        await expect(page.locator('.motionCommentReplies .motionComment')).toContainText(
            'This is a reply',
        );
        await expect(page.locator('.motionComment .delLink')).not.toBeAttached();
    });

    test('see the comment on the sidebar and the feed', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await expect(page.locator('#sidebar .comments')).toContainText('My Name');
    });

    test('disable comments for this specific amendment and delete the comment', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await page
            .locator('.amendment1 .edit, .amendment1 [href*="edit"]')
            .first()
            .click();
        await page.locator('.preventFunctionality .notCommentable input').check();
        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.commentsDeactivatedHint')).toBeVisible();
        await expect(page.locator('#comment_-1_-1_text')).not.toBeVisible();

        await expect(page.locator('#commentsTitle')).toBeVisible();
        await page.locator('section.comments #comment1 .delLink button').click();
        await acceptBootbox(page);

        await expect(page.locator('section.comments .motionComment')).not.toContainText('My Name');
        await expect(page.locator('section.comments .motionComment')).not.toContainText('Some Text');
    });
});