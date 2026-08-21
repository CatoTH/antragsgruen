import { test, expect } from '../../fixtures';
import { loginAsProposalAdmin, loginAsStdUser, logout } from '../../utils/auth';

test.describe('Proposed procedure: modification visibility', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('modifications are visible to admins, initiators, hidden when logged out', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdUser(page);
        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/281');
        await expect(page.locator('#sidebar .withdraw')).toBeVisible();
        await expect(page.locator('body')).not.toContainText('brains');

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/283');
        await expect(page.locator('#sidebar .withdraw')).toBeVisible();
        await expect(page.locator('body')).not.toContainText('brains');

        await logout(page);
        await loginAsProposalAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/281');
        await expect(page.locator('body')).toContainText('brains');
        await expect(page.locator('h2')).toContainText('Verfahrensvorschlag:');
        await expect(page.locator('#proposedChanges')).toHaveCount(0);
        await page.locator('.proposedChangesOpener button').click();
        await page.waitForTimeout(300);
        await expect(page.locator('#proposedChanges')).toBeVisible();
        await page.locator('#proposedChanges .notifyProposer').click();
        await expect(page.locator('.notifyProposerSection')).toBeVisible();
        await page.locator('.notifyProposerSection button').click();
        await page.waitForTimeout(500);
        await expect(page.locator('.notificationStatus')).toContainText('Noch keine Bestätigung');

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/283');
        await expect(page.locator('body')).toContainText('brains');
        await expect(page.locator('h2')).toContainText('Verfahrensvorschlag zu Ä3:');
        await expect(page.locator('#proposedChanges')).toBeVisible();
        await page.locator('#proposedChanges .notifyProposer').click();
        await expect(page.locator('.notifyProposerSection')).toBeVisible();
        await page.locator('.notifyProposerSection button').click();
        await page.waitForTimeout(500);
        await expect(page.locator('.notificationStatus')).toContainText('Noch keine Bestätigung');

        await logout(page);
        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/281');
        await expect(page.locator('body')).not.toContainText('brains');
        await expect(page.locator('h2')).not.toContainText('Verfahrensvorschlag:');

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/283');
        await expect(page.locator('body')).not.toContainText('brains');
        await expect(page.locator('h2')).not.toContainText('Verfahrensvorschlag zu Ä3:');

        await loginAsStdUser(page);
        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/281');
        await expect(page.locator('body')).toContainText('brains');
        await expect(page.locator('h2')).toContainText('Verfahrensvorschlag:');

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/283');
        await expect(page.locator('body')).toContainText('brains');
        await expect(page.locator('h2')).toContainText('Verfahrensvorschlag zu Ä3:');
    });
});
