import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, logout } from '../../utils/auth';

test.describe('Amendments: SimpleDeadline', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('set the deadline to the past', async ({ page }) => {
        await test.step('set the deadline to the past', async () => {
            await new ConsultationHomePage(page).open();
            await loginAsStdAdmin(page);
            await page.locator('#adminLink').click();
            await page.locator('.motionType1').click();

            const pastDate = await page.evaluate(() => {
                const d = new Date(Date.now() - 10000);
                const pad = (n: number) => String(n).padStart(2, '0');
                return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()} 00:00:00`;
            });
            await page.locator('#typeSimpleDeadlineAmendments').first().fill(pastDate);
            await page.locator('.adminTypeForm [name="save"]').first().click();
        });

        await test.step('still see the link as an admin and open the form', async () => {
            await new ConsultationHomePage(page).gotoMotionView(3);
            await test.step('still see it as an admin', async () => {
                await expect(page.locator('.amendmentCreate a').first()).toBeVisible();
                await page.locator('.amendmentCreate a').click();
                await expect(page.locator('h1')).toContainText(
                    'ÄNDERUNGSANTRAG ZU A3: TEXTFORMATIERUNGEN STELLEN',
                );
            });
        });

        await test.step('get an error as a normal user', async () => {
            await logout(page);
            await expect(page.locator('h1').getByText('ÄNDERUNGSANTRAG ANLEGEN').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.alert-danger')).toContainText(
                'Keine Berechtigung zum Anlegen von Änderungsanträgen.',
            );
            await new ConsultationHomePage(page).gotoMotionView(3);
            await expect(page.locator('.amendmentCreate a').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.amendmentCreate')).toContainText(
                'Der Antragsschluss ist vorbei',
            );
        });

        await test.step('set the deadline to the future', async () => {
            await page.locator('#adminLink').click();
            await page.locator('.motionType1').click();

            const futureDate = await page.evaluate(() => {
                const d = new Date(Date.now() + 3600 * 24 * 1000);
                const pad = (n: number) => String(n).padStart(2, '0');
                return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()} 00:00:00`;
            });
            await page.locator('#typeSimpleDeadlineAmendments').first().fill(futureDate);
            await page.locator('.adminTypeForm [name="save"]').first().click();

            await new ConsultationHomePage(page).gotoMotionView(3);
            await expect(page.locator('.amendmentCreate a').first()).toBeVisible();
        });
    });
});