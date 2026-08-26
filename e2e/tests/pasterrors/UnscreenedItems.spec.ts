import { test, expect } from '../../fixtures';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminAppearancePage } from '../../pages/AdminAppearancePage';
import { loginAsStdAdmin } from '../../utils/auth';
import { setCkEditorContent } from '../../utils/dom';

test.describe('UnscreenedItems', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('unscreened motion should not be visible in any start layout', async ({ page }) => {
        await page.goto('/stdparteitag/bdk');
        await loginAsStdAdmin(page);

        await test.step('create a unscreened motion', async () => {
            await page.locator('#sidebar .createMotion').click();

            const title = 'Nicht freigeschalteter Testantrag';

            await page.locator("[name='sections[20]']").first().fill(title);
            await setCkEditorContent(page, 'sections_21_wysiwyg', '<p><strong>Test</strong></p>');
            await setCkEditorContent(page, 'sections_22_wysiwyg', '<p><strong>Test 2</strong></p>');
            await page.locator('#personTypeOrga').first().check();
            await page.locator('#initiatorPrimaryName').first().fill('Mein Name');
            await page.locator('#initiatorEmail').first().fill('test@example.org');
            await page.locator('#resolutionDate').first().fill('01.01.2000');
            await page.locator('#motionEditForm [name="save"]').click();
            await page.locator('#motionConfirmForm [name="confirm"]').click();
            await page.goto('/stdparteitag/bdk');

            const layoutIds = await page.evaluate(() => {
                const w = window as any;
                const list = w.ConsultationStartLayouts || {};
                const result: string[] = [];
                for (const k of Object.keys(list)) result.push(k);
                return result.length > 0 ? result : ['0', '1', '2', '3', '4', '5'];
            });

            for (const layoutId of layoutIds) {
                await page.locator('#adminTodo').click();
                await expect(page.locator('body')).toContainText(title);

                const appearance = new AdminAppearancePage(page);
                await new AdminIndexPage(page).open();
                await page.goto('/stdparteitag/bdk/admin/appearance');
                await page.locator('#startLayoutType').first().selectOption(layoutId);
                await page.locator('#consultationAppearanceForm [name="save"]').click();

                await page.goto('/stdparteitag/bdk');
                await expect(page.locator('body')).not.toContainText(title, { useInnerText: true });
            }
        });
    });
});