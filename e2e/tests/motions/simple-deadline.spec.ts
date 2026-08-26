import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';

function formatDeadline(date: Date): string {
    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const yyyy = date.getFullYear();
    return `${dd}.${mm}.${yyyy} 00:00:00`;
}

test.describe('Simple motion deadline', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a past deadline hides the create link but admins may still create', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await expect(page.locator('body')).toContainText('Antrag stellen');

        await loginAsStdAdmin(page);
        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await test.step('set the deadline to the past', async () => {
            await page
                .locator('#typeSimpleDeadlineMotions')
                .fill(formatDeadline(new Date(Date.now() - 10_000)));
            await page.locator('.adminTypeForm [name="save"]').first().click();

            await home.open();
            await expect(page.locator('body')).not.toContainText('Antrag stellen', { useInnerText: true });

            const motionList = new AdminMotionListPage(page);
            await motionList.open();
        });

        await test.step('access the page as admin', async () => {
            await page.locator('#newMotionBtn').click();
            await page.locator('.createMotion1').click();
            await expect(page.locator('h1')).toContainText('Antrag stellen');

            await logout(page);
        });

        await test.step('access the page as normal user', async () => {
            await expect(page.locator('.alert-danger')).toContainText(
                'Keine Berechtigung zum Anlegen von Anträgen.',
            );
        });
    });

    test('a future deadline shows the create link again', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await test.step('set the deadline to the future', async () => {
            await page
                .locator('#typeSimpleDeadlineMotions')
                .fill(formatDeadline(new Date(Date.now() - 10_000)));
            await page.locator('.adminTypeForm [name="save"]').first().click();

            await home.open();
            await expect(page.locator('body')).not.toContainText('Antrag stellen', { useInnerText: true });

            await motionType.open({ motionTypeId: 1 });
            await page
                .locator('#typeSimpleDeadlineMotions')
                .fill(formatDeadline(new Date(Date.now() + 3600 * 24 * 1000)));
            await page.locator('.adminTypeForm [name="save"]').first().click();

            await home.open();
            await expect(page.locator('body')).toContainText('Antrag stellen');
        });
    });
});
