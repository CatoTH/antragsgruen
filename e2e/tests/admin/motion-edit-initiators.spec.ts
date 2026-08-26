import { test, expect } from '../../fixtures';
import { dispatchClick } from '../../utils/dom';
import { loginAndGotoMotionList, gotoMotionList } from '../../utils/navigation';

test.describe('Admin: MotionEditInitiators', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('edit an initiator, try setting an invalid user', async ({ page }) => {
        await test.step('edit an initiator, try setting an invalid user', async () => {
            const list = await loginAndGotoMotionList(page, 'bdk', 'bdk');
            const motion = await list.gotoMotionEdit(4);

            await expect(page.locator('.supporterForm')).toContainText(
                'E-Mail: testuser@example.org',
            );
            await page.locator('#initiatorPrimaryName').first().fill('Another test user');
            await page.locator('#initiatorOrga').first().fill('KV Test');
            await page.locator('#initiatorEmail').first().fill('test2@example.org');
            await page.locator('#initiatorPhone').first().fill('01234567');
            await motion.saveForm();

            await expect(page.locator('.initiatorSetUsername').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.initiatorCurrentUsername .btnEdit');
            await expect(page.locator('.initiatorCurrentUsername').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.initiatorSetUsername').first()).toBeVisible();
            await page.locator('#initiatorSetUsername').first().fill('invalid@example.org');

            await motion.saveForm();

            await expect(page.locator('.alert')).toContainText('Benutzer*in nicht gefunden');
            await expect(page.locator('.supporterForm')).toContainText(
                'E-Mail: testuser@example.org',
            );
        });

        await test.step('confirm the changes are saved, unassign the user', async () => {
            const list = await gotoMotionList(page);
            const motion = await list.gotoMotionEdit(4);

            await expect(page.locator('.supporterForm')).toContainText(
                'E-Mail: testuser@example.org',
            );
            await expect(page.locator('#initiatorPrimaryName')).toHaveValue('Another test user');
            await expect(page.locator('#initiatorOrga')).toHaveValue('KV Test');
            await expect(page.locator('#initiatorEmail')).toHaveValue('test2@example.org');
            await expect(page.locator('#initiatorPhone')).toHaveValue('01234567');

            await dispatchClick(page, '.initiatorCurrentUsername .btnEdit');
            await expect(page.locator('.initiatorSetUsername').first()).toBeVisible();
            await page.locator('#initiatorSetUsername').first().fill('');

            await motion.saveForm();

            await expect(page.locator('.supporterForm').getByText('E-Mail: testuser@example.org').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('assign the user again', async () => {
            await dispatchClick(page, '.initiatorCurrentUsername .btnEdit');
            await expect(page.locator('.initiatorSetUsername').first()).toBeVisible();
            await page.locator('#initiatorSetUsername').first().fill('testuser@example.org');

            await page.locator('#motionUpdateForm [name="save"]').first().click();

            await expect(page.locator('.supporterForm')).toContainText(
                'E-Mail: testuser@example.org',
            );
        });
    });
});
