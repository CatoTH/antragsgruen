import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: MotionEditInitiators', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('edit an initiator, try setting an invalid user', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await page
            .locator('.motion4 .edit, .motion4 [href*="edit"]')
            .first()
            .click();
        await expect(page.locator('.supporterForm')).toContainText('E-Mail: testuser@example.org');
        await page.locator('#initiatorPrimaryName').fill('Another test user');
        await page.locator('#initiatorOrga').fill('KV Test');
        await page.locator('#initiatorEmail').fill('test2@example.org');
        await page.locator('#initiatorPhone').fill('01234567');
        await page.locator('#motionUpdateForm [name="save"]').click();

        await expect(page.locator('.initiatorSetUsername')).not.toBeVisible();
        await dispatchClick(page, '.initiatorCurrentUsername .btnEdit');
        await expect(page.locator('.initiatorCurrentUsername')).not.toBeVisible();
        await expect(page.locator('.initiatorSetUsername')).toBeVisible();
        await page.locator('#initiatorSetUsername').fill('invalid@example.org');

        await page.locator('#motionUpdateForm [name="save"]').click();

        await expect(page.locator('.alert')).toContainText('Benutzer*in nicht gefunden');
        await expect(page.locator('.supporterForm')).toContainText('E-Mail: testuser@example.org');
    });

    test('confirm the changes are saved, unassign the user', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await page
            .locator('.motion4 .edit, .motion4 [href*="edit"]')
            .first()
            .click();
        await expect(page.locator('.supporterForm')).toContainText('E-Mail: testuser@example.org');
        await expect(page.locator('#initiatorPrimaryName')).toHaveValue('Another test user');
        await expect(page.locator('#initiatorOrga')).toHaveValue('KV Test');
        await expect(page.locator('#initiatorEmail')).toHaveValue('test2@example.org');
        await expect(page.locator('#initiatorPhone')).toHaveValue('01234567');

        await dispatchClick(page, '.initiatorCurrentUsername .btnEdit');
        await expect(page.locator('.initiatorSetUsername')).toBeVisible();
        await page.locator('#initiatorSetUsername').fill('');

        await page.locator('#motionUpdateForm [name="save"]').click();

        await expect(page.locator('.supporterForm')).not.toContainText('E-Mail: testuser@example.org');
    });

    test('assign the user again', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await page
            .locator('.motion4 .edit, .motion4 [href*="edit"]')
            .first()
            .click();

        await dispatchClick(page, '.initiatorCurrentUsername .btnEdit');
        await expect(page.locator('.initiatorSetUsername')).toBeVisible();
        await page.locator('#initiatorSetUsername').fill('testuser@example.org');

        await page.locator('#motionUpdateForm [name="save"]').click();

        await expect(page.locator('.supporterForm')).toContainText('E-Mail: testuser@example.org');
    });
});