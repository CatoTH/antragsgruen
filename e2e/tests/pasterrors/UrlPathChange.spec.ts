import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/BasePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('UrlPathChange', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('change consultation URL path and verify routing', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);

        await new AdminIndexPage(page).open();
        const consultationPage = new AdminConsultationPage(page);
        await consultationPage.open();
        await expect(page.locator('#consultationPath')).toHaveCount(0);
        await page.locator('.urlPathHolder .shower a').click();
        await expect(page.locator('#consultationPath')).toBeVisible();
        await page.locator('#consultationPath').fill('38');
        await page.locator('#consultationSettingsForm [name="save"]').click();
        await expect(page.locator('#consultationPath')).toHaveValue('38');

        await page.goto('/stdparteitag/38');
        await expect(page.locator('h1')).toContainText('Test2');

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('h1')).not.toContainText('Test2');
        await expect(page.locator('body')).toContainText(
            'Die angegebene Veranstaltung wurde nicht gefunden',
        );
    });
});