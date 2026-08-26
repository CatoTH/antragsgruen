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
            .locator('.adminMotionTable .motion2 .titleCol a')
            .first()
            .click();

        await expect(page.locator('#motionStatusString').first()).toBeVisible();
        await expect(page.locator('#motionStatusMotion').filter({ visible: true })).toHaveCount(0);
        await page.locator('#motionStatus').first().selectOption('32');
        await expect(page.locator('#motionStatusString').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#motionStatusMotion').first()).toBeVisible();
        await page.locator('#motionStatusMotion').first().selectOption('3');
        await page.locator('#motionUpdateForm [name="save"]').click();

        await expect(page.locator('#motionStatusMotion').first()).toBeVisible();
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
            .locator('.adminMotionTable .motion2 .titleCol a')
            .first()
            .click();

        await expect(page.locator('#motionStatusAmendment').filter({ visible: true })).toHaveCount(0);
        await page.locator('#motionStatus').first().selectOption('22');
        await expect(page.locator('#motionStatusString').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#motionStatusMotion').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#motionStatusAmendment').first()).toBeVisible();
        await page.locator('#motionStatusAmendment').first().selectOption('279');
        await page.locator('#motionUpdateForm [name="save"]').click();

        await expect(page.locator('#motionStatusAmendment').first()).toBeVisible();
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
            .locator('.adminMotionTable .amendment1 .titleCol a')
            .first()
            .click();

        await expect(page.locator('#amendmentStatusString').first()).toBeVisible();
        await expect(page.locator('#amendmentStatusMotion').filter({ visible: true })).toHaveCount(0);
        await page.locator('#amendmentStatus').first().selectOption('32');
        await expect(page.locator('#amendmentStatusString').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#amendmentStatusMotion').first()).toBeVisible();
        await page.locator('#amendmentStatusMotion').first().selectOption('3');
        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await expect(page.locator('#amendmentStatusMotion').first()).toBeVisible();
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
            .locator('.adminMotionTable .amendment1 .titleCol a')
            .first()
            .click();

        await expect(page.locator('#amendmentStatusAmendment').filter({ visible: true })).toHaveCount(0);
        await page.locator('#amendmentStatus').first().selectOption('22');
        await expect(page.locator('#amendmentStatusString').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#amendmentStatusMotion').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#amendmentStatusAmendment').first()).toBeVisible();
        await page.locator('#amendmentStatusAmendment').first().selectOption('279');
        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await expect(page.locator('#amendmentStatusAmendment').first()).toBeVisible();
        const selected = await page.evaluate(
            () =>
                (document.getElementById('amendmentStatusAmendment') as HTMLSelectElement)?.selectedOptions?.[0]?.text,
        );
        expect(selected).toEqual('Ä1 zu A8');
        await page.locator('#sidebar .view').click();
        await expect(page.locator('.motionDataTable .statusRow a')).toContainText('Ä1 zu A8');
    });
});