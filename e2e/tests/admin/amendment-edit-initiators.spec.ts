import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';
import {
    gotoConsultationHome,
    gotoMotionList,
    gotoStdAdminPage,
} from '../../utils/navigation';

// app\models\supportTypes\SupportBase::GIVEN_BY_INITIATOR
const GIVEN_BY_INITIATOR = '1';
// app\models\db\ISupporter::PERSON_NATURAL
const PERSON_NATURAL = '0';

test.describe('Admin: AmendmentEditInitiators', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('edit an initiator, try setting an invalid user', async ({ page }) => {
        await test.step('edit an initiator, try setting an invalid user', async () => {
            await gotoConsultationHome(page);
            await loginAsStdAdmin(page);
            const admin = await gotoStdAdminPage(page);
            const motionType = await admin.gotoMotionTypes(1);
            await page.locator('#typeSupportType').first().selectOption(GIVEN_BY_INITIATOR);
            await motionType.saveForm();

            const list = await gotoMotionList(page);
            const amendment = await list.gotoAmendmentEdit(2);
            await expect(page.locator('.supporterForm')).toContainText(
                'E-Mail: testuser@example.org',
            );
            await expect(page.locator('#initiatorOrga').filter({ visible: true })).toHaveCount(0);
            // The Cept selects by value within the radio group, which lands on #personTypeNatural
            await page
                .locator(`input[name="Initiator[personType]"][value="${PERSON_NATURAL}"]`)
                .check();
            await page.locator('#initiatorPrimaryName').first().fill('Another test user');
            await page.locator('#initiatorOrga').first().fill('KV Test');
            await page.locator('#initiatorEmail').first().fill('test2@example.org');
            await page.locator('#initiatorPhone').first().fill('01234567');
            await amendment.saveForm();

            await expect(page.locator('.initiatorSetUsername').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.initiatorCurrentUsername .btnEdit');
            await expect(page.locator('.initiatorCurrentUsername').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.initiatorSetUsername').first()).toBeVisible();
            await page.locator('#initiatorSetUsername').first().fill('invalid@example.org');

            await amendment.saveForm();

            await expect(page.locator('.alert')).toContainText('Benutzer*in nicht gefunden');
            await expect(page.locator('.supporterForm')).toContainText(
                'E-Mail: testuser@example.org',
            );
        });

        await test.step('confirm the changes are saved, unassign the user', async () => {
            const list = await gotoMotionList(page);
            const amendment = await list.gotoAmendmentEdit(2);
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

            await amendment.saveForm();

            await expect(page.locator('.supporterForm').getByText('E-Mail: testuser@example.org').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('assign the user again', async () => {
            await dispatchClick(page, '.initiatorCurrentUsername .btnEdit');
            await expect(page.locator('.initiatorSetUsername').first()).toBeVisible();
            await page.locator('#initiatorSetUsername').first().fill('testuser@example.org');

            await page.locator('#amendmentUpdateForm [name="save"]').click();

            await expect(page.locator('.supporterForm')).toContainText(
                'E-Mail: testuser@example.org',
            );
        });
    });
});
