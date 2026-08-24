import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { FIRST_FREE_COMMENT_ID } from '../../utils/constants';

test.describe('Amendments: CommentScreening', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable screening', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await expect(page.locator('#adminTodo')).not.toBeVisible();
        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();
        await expect(page.locator('#screeningComments')).not.toBeChecked();
        await page.locator('#screeningComments').check();
        await page.locator('#consultationSettingsForm [name="save"]').click();
        await expect(page.locator('#screeningComments')).toBeChecked();
        await logout(page);
    });

    test('write comments that go to the screening queue', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await new ConsultationHomePage(page).gotoAmendmentView(1);

        await expect(page.locator('section.comments')).toContainText('Kommentar schreiben');
        await page.locator('#comment_-1_-1_name').fill('Mein Name 2');
        await page.locator('#comment_-1_-1_email').fill('');
        await page.locator('#comment_-1_-1_text').fill('Noch ein zweiter Kommentar');
        await page.locator('section.comments .commentForm [name="writeComment"]').click();

        await expect(page.locator('section.comments .motionComment')).not.toContainText('Mein Name 2');
        await expect(page.locator('section.comments .motionComment')).not.toContainText(
            'Noch ein zweiter Kommentar',
        );
        await expect(page.locator('section.comments')).toContainText(
            '1 Kommentar wartet auf Freischaltung',
        );

        await page.locator('#comment_-1_-1_name').fill('Mein Name 3');
        await page.locator('#comment_-1_-1_email').fill('testuser@example.org');
        await page.locator('#comment_-1_-1_text').fill('Noch ein dritter Kommentar');
        await page.locator('section.comments .commentForm [name="writeComment"]').click();

        await expect(page.locator('section.comments .motionComment')).not.toContainText('Mein Name 3');
        await expect(page.locator('section.comments .motionComment')).not.toContainText(
            'Noch ein dritter Kommentar',
        );
        await expect(page.locator('section.comments')).toContainText(
            '2 Kommentare warten auf Freischaltung',
        );
    });

    test('screen the comments as an admin', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);

        await page.locator('#adminTodo').click();
        await expect(
            page.locator(`.adminTodo .amendmentCommentScreen${FIRST_FREE_COMMENT_ID + 0}`),
        ).toBeVisible();
        await expect(
            page.locator(`.adminTodo .amendmentCommentScreen${FIRST_FREE_COMMENT_ID + 1}`),
        ).toBeVisible();
        await page
            .locator(`.adminTodo .amendmentCommentScreen${FIRST_FREE_COMMENT_ID + 1} a`)
            .click();

        await expect(page.locator('section.comments .motionComment')).toContainText('Mein Name 2');
        await expect(page.locator('section.comments .motionComment')).toContainText(
            'Noch ein zweiter Kommentar',
        );
        await expect(page.locator('section.comments')).toContainText(
            '2 Kommentare warten auf Freischaltung',
        );
        const commId = FIRST_FREE_COMMENT_ID + 0;
        await expect(page.locator(`#comment${commId}`)).toContainText('noch nicht freigeschaltet');
        await page.locator(`#comment${commId} form.screening [name="commentScreeningAccept"]`).click();

        await expect(page.locator('section.comments')).toContainText(
            '1 Kommentar wartet auf Freischaltung',
        );
        const commId2 = FIRST_FREE_COMMENT_ID + 1;
        await expect(page.locator(`#comment${commId2}`)).toContainText('noch nicht freigeschaltet');
        await page.locator(`#comment${commId2} form.screening [name="commentScreeningReject"]`).click();

        await expect(page.locator('.commentScreeningQueue')).not.toBeVisible();
        await expect(page.locator('body')).toContainText('Noch ein zweiter Kommentar');
        await expect(page.locator('body')).not.toContainText('Noch ein dritter Kommentar');
    });

    test('verify there is no todo anymore', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await expect(page.locator('#adminTodo')).not.toBeVisible();
    });
});