import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { FIRST_FREE_COMMENT_ID } from '../../utils/constants';

test.describe('Amendments: CommentScreening', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable screening', async ({ page }) => {
        await test.step('enable screening', async () => {
            await new ConsultationHomePage(page).open();
            await loginAsStdAdmin(page);
            await test.step('write a comment (logged out)', async () => {
                await expect(page.locator('#adminTodo').filter({ visible: true })).toHaveCount(0);
                await page.locator('#adminLink').click();
                await page.locator('#consultationLink').click();
                await expect(page.locator('#screeningComments')).not.toBeChecked();
                await page.locator('#screeningComments').first().check();
                await page.locator('#consultationSettingsForm [name="save"]').click();
                await expect(page.locator('#screeningComments')).toBeChecked();
                await logout(page);
            });
        });

        await test.step('write comments that go to the screening queue', async () => {
            await new ConsultationHomePage(page).gotoAmendmentView(1);

            await expect(page.locator('section.comments')).toContainText('Kommentar schreiben');
            await page.locator('#comment_-1_-1_name').first().fill('Mein Name 2');
            await page.locator('#comment_-1_-1_email').first().fill('');
            await page.locator('#comment_-1_-1_text').first().fill('Noch ein zweiter Kommentar');
            await page.locator('section.comments .commentForm [name="writeComment"]').click();

            await expect(page.locator('section.comments .motionComment').getByText('Mein Name 2').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('section.comments .motionComment').getByText('Noch ein zweiter Kommentar').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('section.comments')).toContainText(
                '1 Kommentar wartet auf Freischaltung',
            );

            await page.locator('#comment_-1_-1_name').first().fill('Mein Name 3');
            await page.locator('#comment_-1_-1_email').first().fill('testuser@example.org');
            await page.locator('#comment_-1_-1_text').first().fill('Noch ein dritter Kommentar');
            await page.locator('section.comments .commentForm [name="writeComment"]').click();

            await expect(page.locator('section.comments .motionComment').getByText('Mein Name 3').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('section.comments .motionComment').getByText('Noch ein dritter Kommentar').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('section.comments')).toContainText(
                '2 Kommentare warten auf Freischaltung',
            );
        });

        await test.step('screen the comments as an admin', async () => {
            await test.step('enable screening and force e-mails', async () => {
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
            });

            await test.step('write a comment (with screening)', async () => {
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

                await expect(page.locator('.commentScreeningQueue').filter({ visible: true })).toHaveCount(0);
                await expect(page.locator('body')).toContainText('Noch ein zweiter Kommentar');
                await expect(page.locator('body')).not.toContainText('Noch ein dritter Kommentar', { useInnerText: true });
            });
        });

        await test.step('verify there is no todo anymore', async () => {
            await expect(page.locator('#adminTodo').filter({ visible: true })).toHaveCount(0);
        });
    });
});