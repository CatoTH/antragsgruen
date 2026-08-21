import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Admin: ObsoletedByStatus', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('set a motion to be obsoleted by another motion', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await page
            .locator('.motion2 .edit, .motion2 [href*="edit"]')
            .first()
            .click();

        await expect(page.locator('#motionStatusString')).toBeVisible();
        await expect(page.locator('#motionStatusMotion')).not.toBeVisible();
        await page.locator('#motionStatus').selectOption('32');
        await expect(page.locator('#motionStatusString')).not.toBeVisible();
        await expect(page.locator('#motionStatusMotion')).toBeVisible();
        await page.locator('#motionStatusMotion').selectOption('3');
        await page.locator('#motionUpdateForm [name="save"]').click();

        await expect(page.locator('#motionStatusMotion')).toBeVisible();
        const selected = await page.evaluate(
            () =>
                (document.getElementById('motionStatusMotion') as HTMLSelectElement)?.selectedOptions?.[0]?.text,
        );
        expect(selected).toEqual('A3');
        await page.locator('#sidebar .view').click();
        await expect(page.locator('.motionDataTable .statusRow a')).toContainText('A3');
    });

    test('set a motion to be obsoleted by another amendment', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await page
            .locator('.motion2 .edit, .motion2 [href*="edit"]')
            .first()
            .click();

        await expect(page.locator('#motionStatusAmendment')).not.toBeVisible();
        await page.locator('#motionStatus').selectOption('22');
        await expect(page.locator('#motionStatusString')).not.toBeVisible();
        await expect(page.locator('#motionStatusMotion')).not.toBeVisible();
        await expect(page.locator('#motionStatusAmendment')).toBeVisible();
        await page.locator('#motionStatusAmendment').selectOption('279');
        await page.locator('#motionUpdateForm [name="save"]').click();

        await expect(page.locator('#motionStatusAmendment')).toBeVisible();
        const selected = await page.evaluate(
            () =>
                (document.getElementById('motionStatusAmendment') as HTMLSelectElement)?.selectedOptions?.[0]?.text,
        );
        expect(selected).toEqual('Ä1 zu A8');
        await page.locator('#sidebar .view').click();
        await expect(page.locator('.motionDataTable .statusRow a')).toContainText('Ä1 zu A8');
    });

    test('set an amendment to be obsoleted by another motion', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await page
            .locator('.amendment1 .edit, .amendment1 [href*="edit"]')
            .first()
            .click();

        await expect(page.locator('#amendmentStatusString')).toBeVisible();
        await expect(page.locator('#amendmentStatusMotion')).not.toBeVisible();
        await page.locator('#amendmentStatus').selectOption('32');
        await expect(page.locator('#amendmentStatusString')).not.toBeVisible();
        await expect(page.locator('#amendmentStatusMotion')).toBeVisible();
        await page.locator('#amendmentStatusMotion').selectOption('3');
        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await expect(page.locator('#amendmentStatusMotion')).toBeVisible();
        const selected = await page.evaluate(
            () =>
                (document.getElementById('amendmentStatusMotion') as HTMLSelectElement)?.selectedOptions?.[0]?.text,
        );
        expect(selected).toEqual('A3');
        await page.locator('#sidebar .view').click();
        await expect(page.locator('.motionDataTable .statusRow a')).toContainText('A3');
    });

    test('set an amendment to be obsoleted by another amendment', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await page
            .locator('.amendment1 .edit, .amendment1 [href*="edit"]')
            .first()
            .click();

        await expect(page.locator('#amendmentStatusAmendment')).not.toBeVisible();
        await page.locator('#amendmentStatus').selectOption('22');
        await expect(page.locator('#amendmentStatusString')).not.toBeVisible();
        await expect(page.locator('#amendmentStatusMotion')).not.toBeVisible();
        await expect(page.locator('#amendmentStatusAmendment')).toBeVisible();
        await page.locator('#amendmentStatusAmendment').selectOption('279');
        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await expect(page.locator('#amendmentStatusAmendment')).toBeVisible();
        const selected = await page.evaluate(
            () =>
                (document.getElementById('amendmentStatusAmendment') as HTMLSelectElement)?.selectedOptions?.[0]?.text,
        );
        expect(selected).toEqual('Ä1 zu A8');
        await page.locator('#sidebar .view').click();
        await expect(page.locator('.motionDataTable .statusRow a')).toContainText('Ä1 zu A8');
    });
});