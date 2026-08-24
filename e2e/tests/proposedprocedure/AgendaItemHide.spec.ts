import { test, expect } from '../../fixtures';
import { loginAsGlobalAdmin } from '../../utils/auth';

test.describe('Proposed procedure: Agenda item hide', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('hide agenda item from proposed procedure', async ({ page }) => {
        await page.goto('/laenderrat-to/laenderrat-to');

        await expect(page.locator('#agendaitem_9')).toContainText('Zeitpolitik');
        await expect(page.locator('.motionLink64')).toContainText('Zeitpolitik');
        await expect(page.locator('#sidebar #proposedProcedureLink')).toBeVisible();
        await page.locator('#sidebar #proposedProcedureLink').click();

        await expect(page.locator('body')).toContainText('Abstimmung: Zeitpolitik');
        await expect(page.locator('body')).toContainText('Z-01');
        await expect(page.locator('body')).toContainText('Z-01-115-1');

        await expect(page.locator('body')).toContainText('Abstimmung: Tagesordnung');
        await expect(page.locator('body')).toContainText('F-01');
        await expect(page.locator('body')).toContainText('W-01');

        await page.goto('/laenderrat-to/laenderrat-to');
        await loginAsGlobalAdmin(page);

        await page.locator('.agendaEditLink').click();

        await expect(
            page.locator('.agendaEditWidget .item_9 .extraSettings'),
        ).toBeVisible();
        await page.evaluate(() => {
            const btn = document.querySelector(
                '.agendaEditWidget .item_9 .extraSettings button',
            ) as HTMLElement | null;
            if (btn) btn.click();
        });
        await page.waitForTimeout(500);
        await page.locator('.agendaEditWidget .item_9 .extraSettings .inProposedProcedures input').uncheck();

        await page.evaluate(() => {
            const btn = document.querySelector('.agendaEditWidget .btnSave') as HTMLElement | null;
            if (btn) btn.click();
        });
        await page.waitForTimeout(1000);

        await page.goto('/laenderrat-to/laenderrat-to');
        await page.locator('#sidebar #proposedProcedureLink').click();

        await expect(page.locator('body')).not.toContainText('Abstimmung: Zeitpolitik');
        await expect(page.locator('body')).not.toContainText('Z-01');
        await expect(page.locator('body')).not.toContainText('Z-01-115-1');
        await expect(page.locator('body')).toContainText('Abstimmung: Tagesordnung');
        await expect(page.locator('body')).toContainText('F-01');
        await expect(page.locator('body')).toContainText('W-01');

        await page.goto('/laenderrat-to/laenderrat-to/admin/motiontypes/type/9');
        await page.locator('#typeProposedProcedure').uncheck();
        await page.locator('.adminTypeForm [name="save"].first()').click();

        await page.goto('/laenderrat-to/laenderrat-to');
        await page.locator('.agendaEditLink').click();

        await expect(
            page.locator('.agendaEditWidget .item_9 .extraSettings'),
        ).toBeVisible();
        await page.evaluate(() => {
            const btn = document.querySelector(
                '.agendaEditWidget .item_9 .extraSettings button',
            ) as HTMLElement | null;
            if (btn) btn.click();
        });
        await page.waitForTimeout(500);
        await expect(
            page.locator('.agendaEditWidget .item_9 .extraSettings .hasSpeakingList'),
        ).toBeVisible();
        await expect(
            page.locator('.agendaEditWidget .item_9 .extraSettings .inProposedProcedures'),
        ).toHaveCount(0);
    });
});
