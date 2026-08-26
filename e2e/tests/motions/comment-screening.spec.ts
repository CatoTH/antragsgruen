import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { FIRST_FREE_COMMENT_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';

async function openMotionWithComments(page: import('@playwright/test').Page): Promise<void> {
    const home = new ConsultationHomePage(page);
    await home.open({ subdomain: 'bdk', consultationPath: 'bdk' });
    await home.gotoMotionView(4);
}

async function enableScreening(page: import('@playwright/test').Page): Promise<void> {
    const consultation = new AdminConsultationPage(page);
    await consultation.open({ subdomain: 'bdk', consultationPath: 'bdk' });
    await expect(page.locator('#screeningComments')).not.toBeChecked();
    await expect(page.locator('#commentNeedsEmail')).not.toBeChecked();
    await page.locator('#screeningComments').first().check();
    await page.locator('#commentNeedsEmail').first().check();
    await consultation.saveForm();
    await expect(page.locator('#screeningComments')).toBeChecked();
    await expect(page.locator('#commentNeedsEmail')).toBeChecked();
}

test.describe('Comment screening', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('logged out users cannot comment', async ({ page }) => {
        await openMotionWithComments(page);

        await test.step('write a comment (logged out)', async () => {
            await expect(page.locator('body')).not.toContainText('Kommentar schreiben', { useInnerText: true });
            await page.locator('#section_21_1 .comment .shower').click();
        });

        await test.step('write a comment (without screening)', async () => {
            await expect(page.locator('#section_21_1').getByText('Kommentar schreiben').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.commentForm').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('body')).toContainText(
                'Logge dich ein, um kommentieren zu können',
            );
        });

    });

    test('a logged-in user can comment without screening', async ({ page }) => {
        await openMotionWithComments(page);
        await loginAsStdUser(page);
        await openMotionWithComments(page);

        await page.locator('#section_21_1 .comment .shower').click();
        await expect(page.locator('#section_21_1')).toContainText('Kommentar schreiben');
        await expect(page.locator('#section_21_1')).toContainText(
            'Testuser (testuser@example.org)',
        );
        await page.locator('#comment_21_1_text').first().fill('Some Text');
        await page.locator('#comment_21_1_form [name="writeComment"]').click();

        await expect(page.locator('#section_21_1 .motionComment')).toContainText('Testuser');
        await expect(page.locator('#section_21_1 .motionComment')).toContainText('Some Text');
    });

    test('screened comments are queued and can be accepted or rejected', async ({ page }) => {
        await openMotionWithComments(page);
        await loginAsStdAdmin(page);
        await test.step('enable screening and force e-mails', async () => {
            await expect(page.locator('#adminTodo').filter({ visible: true })).toHaveCount(0);
            await enableScreening(page);
            await logout(page);

            await openMotionWithComments(page);
            await loginAsStdUser(page);
            await openMotionWithComments(page);
        });

        await page.locator('#section_21_1 .comment .shower').click();
        await page.locator('#comment_21_1_text').first().fill('Noch ein zweiter Kommentar');
        await page.locator('#comment_21_1_form [name="writeComment"]').click();

        await expect(page.locator('#section_21_1 .motionComment').getByText('Noch ein zweiter Kommentar').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#section_21_1')).toContainText(
            '1 Kommentar wartet auf Freischaltung',
        );

        const idBase = `#comment_21_1_${FIRST_FREE_COMMENT_ID}`;
        await expect(page.locator(`${idBase}_form`).filter({ visible: true })).toHaveCount(0);
        await page.locator(`#comment${FIRST_FREE_COMMENT_ID} .replyButton`).click();
        await expect(page.locator(`${idBase}_form`).first()).toBeVisible();
        await page.locator(`${idBase}_text`).first().fill('Noch ein dritter Kommentar');
        await page.locator(`${idBase}_form [name="writeComment"]`).click();

        await expect(page.locator('#section_21_1 .motionComment').getByText('Noch ein dritter Kommentar').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#section_21_1')).toContainText(
            '2 Kommentare warten auf Freischaltung',
        );
        await logout(page);

        const home = new ConsultationHomePage(page);
        await home.open({ subdomain: 'bdk', consultationPath: 'bdk' });
        await loginAsStdAdmin(page);
        await test.step('screen the comment', async () => {
            await page.locator('#adminTodo').click();
            await expect(
                page.locator(`.adminTodo .motionCommentScreen${FIRST_FREE_COMMENT_ID + 1}`),
            ).toBeVisible();
            await expect(
                page.locator(`.adminTodo .motionCommentScreen${FIRST_FREE_COMMENT_ID + 2}`),
            ).toBeVisible();
            await page
                .locator(`.adminTodo .motionCommentScreen${FIRST_FREE_COMMENT_ID + 2} a`)
                .click();

            await expect(page.locator('#section_21_1 .motionComment')).toContainText('Testuser');
            await expect(page.locator('#section_21_1 .motionComment')).toContainText(
                'Noch ein zweiter Kommentar',
            );

            const acceptId = FIRST_FREE_COMMENT_ID + 1;
            await expect(page.locator(`#comment${acceptId}`)).toContainText('noch nicht freigeschaltet');
            await page
                .locator(`#comment${acceptId} form.screening [name="commentScreeningAccept"]`)
                .click();

            await expect(page.locator('#section_21_1')).toContainText(
                '1 Kommentar wartet auf Freischaltung',
            );

            const rejectId = FIRST_FREE_COMMENT_ID + 2;
            await expect(page.locator(`#comment${rejectId}`)).toContainText('noch nicht freigeschaltet');
            await page
                .locator(`#comment${rejectId} form.screening [name="commentScreeningReject"]`)
                .click();

            await expect(page.locator('.commentScreeningQueue').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('body')).toContainText('Noch ein zweiter Kommentar');
            await expect(page.locator('body')).not.toContainText('Noch ein dritter Kommentar', { useInnerText: true });

            await home.open({ subdomain: 'bdk', consultationPath: 'bdk' });
            await expect(page.locator('#adminTodo').filter({ visible: true })).toHaveCount(0);
        });

    });
});
