import { test, expect } from '../../fixtures';
import { loginAsProposalAdmin, loginAsConsultationAdmin, logout } from '../../utils/auth';
import { gotoAmendment } from '../../utils/navigation';

test.describe('Proposed procedure: deactivating', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('disable proposed procedure per motion type', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsProposalAdmin(page);
        await gotoAmendment(page, true, 'Testing_proposed_changes-630', 279);

        await test.step('see the activated proposed procedure', async () => {
            await expect(page.locator('#proposedChanges').filter({ visible: true })).toHaveCount(0);
            await page.evaluate(() => {
                const btn = document.querySelector('.proposedChangesOpener button') as HTMLElement | null;
                if (btn) btn.click();
            });
            await expect(page.locator('#proposedChanges').first()).toBeVisible();

            await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
            await expect(page.locator('#proposedChanges').first()).toBeVisible();

            await page.goto('/stdparteitag/std-parteitag');
            await page.locator('#proposedProcedureLink').click();
            await expect(page.locator('.motionHolder1').first()).toBeVisible();

            await logout(page);
            await loginAsConsultationAdmin(page);
        });

        await test.step('deactivate proposed procedures', async () => {
            await page.locator('#adminLink').click();
            await page.locator('.motionType1').click();
            await expect(page.locator('#typeProposedProcedure')).toBeChecked();
            await page.locator('#typeProposedProcedure').first().uncheck();
            await page.locator('.adminTypeForm [name="save"]').first().click();

            await expect(page.locator('#typeProposedProcedure')).not.toBeChecked();

            await logout(page);
            await loginAsProposalAdmin(page);

            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 279);
        });

        await test.step('confirm the proposed procedures are not visible anymore', async () => {
            await expect(page.locator('#proposedChanges').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.proposedChangesOpener').filter({ visible: true })).toHaveCount(0);

            await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
            await expect(page.locator('#proposedChanges').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.proposedChangesOpener').filter({ visible: true })).toHaveCount(0);

            await page.goto('/stdparteitag/std-parteitag');
            await page.locator('#proposedProcedureLink').click();
            await expect(page.locator('.motionHolder1').filter({ visible: true })).toHaveCount(0);
        });
    });
});
