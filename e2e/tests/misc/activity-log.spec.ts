import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Misc: activity log', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('consultation-wide and per-motion activity log entries', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.locator('#sidebar .activitylog a').click();
        await expect(page.locator('body')).toContainText('Änderungsantrag Ä2 veröffentlicht');
        await expect(page.locator('body')).not.toContainText(
            'Testadmin hat den Verfahrensvorschlag (Version -) bearbeitet',
        );
        await expect(page.locator('body')).toContainText('Testuser hat den Änderungsantrag Ä3');

        await page.locator('#motionListLink').click();
        await expect(page.locator('h1')).toContainText(/liste: anträge/i);
        await page.locator('.motion118 .edit, .motion118 [href*="edit"]').first().click();
        await page.locator('.sidebarActions .activity').click();
        await expect(page.locator('body')).toContainText('Testuser hat den Antrag veröffentlicht');
        await expect(page.locator('body')).toContainText(
            'Testadmin hat den Verfahrensvorschlag (Version -) bearbeitet',
        );

        await page.locator('#motionListLink').click();
        await expect(page.locator('h1')).toContainText(/liste: anträge/i);
        await page.locator('.amendment281 .edit, .amendment281 [href*="edit"]').first().click();
        await page.locator('.sidebarActions .activity').click();
        await expect(page.locator('body')).toContainText('Testuser hat den Änderungsantrag Ä3');
        await expect(page.locator('body')).toContainText(
            'Testadmin hat den Verfahrensvorschlag (Version -) bearbeitet',
        );
    });
});
