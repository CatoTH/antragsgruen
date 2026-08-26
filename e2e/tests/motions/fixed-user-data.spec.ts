import { test, expect } from '../../fixtures';
import { loginAsFixedDataUser } from '../../utils/auth';
import { setCkEditorContent } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

test.describe('Fixed user data', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('the initiator name is read-only for natural persons', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionCreatePage();
        await loginAsFixedDataUser(page);

        await test.step('check that the basic functuanality works', async () => {
            await expect(page.locator('#initiatorPrimaryName')).toHaveValue('Fixed Data');
            await expect(page.locator('#initiatorOrga')).toHaveValue('MotionTools');
        });

        await test.step('submit a motion with a fake name', async () => {
            await expect(page.locator('#initiatorPrimaryName')).toHaveAttribute('readonly', '');

            await page.locator('#personTypeOrga').first().check();
            await expect(page.locator('#initiatorPrimaryName')).not.toHaveAttribute('readonly', '');

            await page.locator('#personTypeNatural').first().check();
            await expect(page.locator('#initiatorPrimaryName')).toHaveAttribute('readonly', '');
        });
    });

    test('a faked initiator name is replaced by the fixed data', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionCreatePage();
        await loginAsFixedDataUser(page);

        await page.evaluate(() => {
            const w = window as any;
            w.$('#initiatorPrimaryName').val('Some fake name');
        });
        await page.locator("input[name='tags[]'][value='1']").first().check();
        await expect(page.locator('#initiatorPrimaryName')).toHaveValue('Some fake name');

        await page.locator('[name="sections[1]"]').first().fill('Test motion');
        await setCkEditorContent(page, 'sections_2_wysiwyg', '<p><strong>Test</strong></p>');
        await setCkEditorContent(page, 'sections_3_wysiwyg', '<p><strong>Test 2</strong></p>');

        await page.locator('#motionEditForm [name="save"]').click();

        await expect(page.locator('body')).toContainText('Test motion');
        await expect(page.locator('body')).not.toContainText('Some fake name', { useInnerText: true });
        await expect(page.locator('body')).toContainText('Fixed Data (MotionTools)');
    });
});
