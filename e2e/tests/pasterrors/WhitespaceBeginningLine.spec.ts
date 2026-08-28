import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { MotionPage } from '../../pages/MotionPage';
import { loginAsStdUser } from '../../utils/auth';

test.describe('WhitespaceBeginningLine', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('amendment without changes preserves leading whitespace', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdUser(page);

        const motion = new MotionPage(page);
        await motion.open({ motionSlug: '114' });
        await expect(page.locator('body')).toContainText('Leerzeichen-Test');
        await page.locator('#sidebar .amendmentCreate a').click();
        await expect(page.locator('.breadcrumb')).toContainText('Änderungsantrag stellen');

        await page.waitForTimeout(1000);
        await page.locator('#initiatorPrimaryName').fill('Mein Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();

        await expect(page.locator('h1')).toContainText('Änderungsantrag bestätigen');
        await expect(page.locator('body')).toContainText('Antragsteller*innen');

        const aria = await page.evaluate(
            () => document.querySelector('del.space')?.getAttribute('aria-label') ?? '',
        );
        expect(aria).toContain('Leerzeichen');
        expect(aria).toContain('Streichen');
    });
});