import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { gotoConsultationHome } from '../../utils/navigation';

// The std-parteitag fixture ships with the "Currently Debated" feature enabled and an ongoing debate on
// motion A2 ("O’zapft is!"). Guests/regular users see the inline widget, admins the moderation widget.

test.describe('Debate: CurrentlyDebated', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('see the currently debated motion as a guest', async ({ page }) => {
        await test.step('see the currently debated motion as a guest', async () => {
            await gotoConsultationHome(page);
            await expect(page.locator('.currentDebateInline .debatedItem')).toBeVisible({
                timeout: 5000,
            });
            await expect(
                page.locator('.currentDebateInline .debatedItem .title'),
            ).toContainText('O’zapft is!');
            // the moderation widget is only rendered for moderators
            await expect(page.locator('.currentDebateAdmin').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('end the ongoing debate as an admin', async () => {
            await loginAsStdAdmin(page);
            await gotoConsultationHome(page);
            // admins get the moderation widget instead of the inline one
            await expect(page.locator('.currentDebateInline').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.currentDebateAdmin .debatedItem')).toBeVisible({
                timeout: 5000,
            });
            await expect(
                page.locator('.currentDebateAdmin .debatedItem .title'),
            ).toContainText('O’zapft is!');
            await page.locator('.currentDebateAdmin .debatedItem .stopDebateBtn').click();
            await expect(page.locator('.currentDebateAdmin')).toContainText(
                'Aktuell findet keine Debatte statt',
                { timeout: 5000 },
            );
        });

        await test.step('start a debate over another motion', async () => {
            await expect(page.locator('#debateAdminSelect-motion')).toBeVisible({ timeout: 5000 });
            // A3: Textformatierungen
            await page.locator('#debateAdminSelect-motion').first().selectOption('3');
            await page
                .locator('.currentDebateAdmin .selectRow-motion .rowButton button')
                .click();
            await expect(page.locator('.currentDebateAdmin .debatedItem')).toBeVisible({
                timeout: 5000,
            });
            await expect(
                page.locator('.currentDebateAdmin .debatedItem .title'),
            ).toContainText('Textformatierungen');
        });

        await test.step('confirm the debated motion is visible to a regular user', async () => {
            await logout(page);
            await loginAsStdUser(page);
            await gotoConsultationHome(page);
            await expect(page.locator('.currentDebateAdmin').filter({ visible: true })).toHaveCount(0);
            await expect(
                page.locator('.currentDebateInline .debatedItem .title'),
            ).toContainText('Textformatierungen');
        });
    });
});
