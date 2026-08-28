import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Admin: GlobalAlternatives', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('deactivate global alternatives', async ({ page }) => {
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await page.locator('#sidebar .amendmentCreate').click();
        await expect(page.locator('.editorialGlobalBar input[name=globalAlternative]')).toBeAttached();

        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();
        await expect(page.locator('#globalAlternatives')).toBeChecked();
        await page.locator('#globalAlternatives').uncheck();
        await page.locator('#consultationSettingsForm [name="save"]').click();
        await expect(page.locator('#globalAlternatives')).not.toBeChecked();

        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await page.locator('#sidebar .amendmentCreate').click();
        await expect(page.locator('.editorialGlobalBar input[name=globalAlternative]')).not.toBeAttached();
    });
});