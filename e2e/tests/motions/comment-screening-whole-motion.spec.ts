import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { FIRST_FREE_COMMENT_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';

test.describe('Whole-motion comment screening', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('screened whole-motion comments can be accepted and rejected', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionView(2);

        await loginAsStdAdmin(page);
        const consultation = new AdminConsultationPage(page);
        await consultation.open();
        await expect(page.locator('#screeningComments')).not.toBeChecked();
        await page.locator('#screeningComments').check();
        await consultation.saveForm();
        await expect(page.locator('#screeningComments')).toBeChecked();
        await logout(page);

        await home.open();
        await home.gotoMotionView(2);

        await expect(page.locator('section.comments')).toContainText('Kommentar schreiben');
        await page.locator('#comment_-1_-1_name').fill('Mein Name 2');
        await page.locator('#comment_-1_-1_email').fill('');
        await page.locator('#comment_-1_-1_text').fill('Noch ein zweiter Kommentar');
        await page.locator('section.comments .commentForm [name="writeComment"]').click();

        await expect(page.locator('section.comments .motionComment')).not.toContainText(
            'Mein Name 2',
        );
        await expect(page.locator('section.comments')).toContainText(
            '1 Kommentar wartet auf Freischaltung',
        );

        await page.locator('#comment_-1_-1_name').fill('Mein Name 3');
        await page.locator('#comment_-1_-1_email').fill('testuser@example.org');
        await page.locator('#comment_-1_-1_text').fill('Noch ein dritter Kommentar');
        await page.locator('section.comments .commentForm [name="writeComment"]').click();

        await expect(page.locator('section.comments .motionComment')).not.toContainText(
            'Mein Name 3',
        );
        await expect(page.locator('section.comments')).toContainText(
            '2 Kommentare warten auf Freischaltung',
        );

        await home.open();
        await loginAsStdAdmin(page);
        await page.locator('#adminTodo').click();
        await expect(
            page.locator(`.adminTodo .motionCommentScreen${FIRST_FREE_COMMENT_ID}`),
        ).toBeVisible();
        await expect(
            page.locator(`.adminTodo .motionCommentScreen${FIRST_FREE_COMMENT_ID + 1}`),
        ).toBeVisible();
        await page
            .locator(`.adminTodo .motionCommentScreen${FIRST_FREE_COMMENT_ID + 1} a`)
            .click();

        await expect(page.locator('section.comments .motionComment')).toContainText('Mein Name 2');
        await expect(page.locator('section.comments .motionComment')).toContainText(
            'Noch ein zweiter Kommentar',
        );

        const acceptId = FIRST_FREE_COMMENT_ID;
        await expect(page.locator(`#comment${acceptId}`)).toContainText('noch nicht freigeschaltet');
        await page
            .locator(`#comment${acceptId} form.screening [name="commentScreeningAccept"]`)
            .click();

        await expect(page.locator('section.comments')).toContainText(
            '1 Kommentar wartet auf Freischaltung',
        );

        const rejectId = FIRST_FREE_COMMENT_ID + 1;
        await expect(page.locator(`#comment${rejectId}`)).toContainText('noch nicht freigeschaltet');
        await page
            .locator(`#comment${rejectId} form.screening [name="commentScreeningReject"]`)
            .click();

        await expect(page.locator('.commentScreeningQueue')).toHaveCount(0);
        await expect(page.locator('body')).toContainText('Noch ein zweiter Kommentar');
        await expect(page.locator('body')).not.toContainText('Noch ein dritter Kommentar');

        await home.open();
        await expect(page.locator('#adminTodo')).toHaveCount(0);
    });
});
