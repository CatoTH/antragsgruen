import { test, expect } from '../../fixtures';
import { loginAsProposalAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { gotoAmendment } from '../../utils/navigation';
import { dispatchClick } from '../../utils/dom';

test.describe('Proposed procedure: modification visibility', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('modifications are visible to admins, initiators, hidden when logged out', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdUser(page);
        await gotoAmendment(page, true, 'Testing_proposed_changes-630', 281);
        await test.step('see my amendments, but not the modified changes', async () => {
            await expect(page.locator('#sidebar .withdraw').first()).toBeVisible();
        });

        await test.step('make the changes visible as admin', async () => {
            await expect(page.locator('body')).not.toContainText('brains', { useInnerText: true });

            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 283);
            await expect(page.locator('#sidebar .withdraw').first()).toBeVisible();
        });

        await test.step('not see the changes logged out', async () => {
            await expect(page.locator('body')).not.toContainText('brains', { useInnerText: true });

            await logout(page);
            await loginAsProposalAdmin(page);

            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 281);
        });

        await test.step('see the changes as initiator', async () => {
            await expect(page.locator('body')).toContainText('brains');
            await expect(page.locator('h2').filter({ hasText: 'Verfahrensvorschlag:' }).first()).toBeVisible();
            await expect(page.locator('#proposedChanges').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.proposedChangesOpener button');
            await page.waitForTimeout(300);
            await expect(page.locator('#proposedChanges').first()).toBeVisible();
            await page.locator('#proposedChanges .notifyProposer').click();
            await expect(page.locator('.notifyProposerSection').first()).toBeVisible();
            await page.locator('.notifyProposerSection button').click();
            await page.waitForTimeout(500);
            await expect(page.locator('.notificationStatus')).toContainText('Noch keine Bestätigung');

            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 283);
            await expect(page.locator('body')).toContainText('brains');
            await expect(page.locator('h2').filter({ hasText: 'Verfahrensvorschlag zu Ä3:' }).first()).toBeVisible();
            await expect(page.locator('#proposedChanges').first()).toBeVisible();
            await page.locator('#proposedChanges .notifyProposer').click();
            await expect(page.locator('.notifyProposerSection').first()).toBeVisible();
            await page.locator('.notifyProposerSection button').click();
            await page.waitForTimeout(500);
            await expect(page.locator('.notificationStatus')).toContainText('Noch keine Bestätigung');

            await logout(page);
            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 281);
            await expect(page.locator('body')).not.toContainText('brains', { useInnerText: true });
            await expect(page.locator('h2').getByText('Verfahrensvorschlag:').filter({ visible: true })).toHaveCount(0);

            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 283);
            await expect(page.locator('body')).not.toContainText('brains', { useInnerText: true });
            await expect(page.locator('h2').getByText('Verfahrensvorschlag zu Ä3:').filter({ visible: true })).toHaveCount(0);

            await loginAsStdUser(page);
            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 281);
            await expect(page.locator('body')).toContainText('brains');
            await expect(page.locator('h2').filter({ hasText: 'Verfahrensvorschlag:' }).first()).toBeVisible();

            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 283);
            await expect(page.locator('body')).toContainText('brains');
            await expect(page.locator('h2').filter({ hasText: 'Verfahrensvorschlag zu Ä3:' }).first()).toBeVisible();
        });
    });
});
