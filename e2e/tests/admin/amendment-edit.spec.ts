import { test, expect } from '../../fixtures';
import { loginAndGotoMotionList, gotoMotionList } from '../../utils/navigation';
import { appendCkEditorContent, setCkEditorContent } from '../../utils/dom';

const STATUS_COMPLETED = '9';

test.describe('Admin: AmendmentEdit', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('edit an amendment', async ({ page }) => {
        await test.step('edit an amendment', async () => {
            const list = await loginAndGotoMotionList(page);
            const amendment = await list.gotoAmendmentEdit(1);

            await expect(page.locator('body')).toContainText('Lorem ipsum dolor sit amet');
            await expect(page.locator('body')).toContainText('Oamoi a Maß');
            await expect(page.locator('body')).toContainText('Auf gehds beim Schichtl');
            await expect(page.locator('#sections_2').filter({ visible: true })).toHaveCount(0);
            await page.locator('#amendmentTextEditCaller button').click();
            await expect(page.locator('#sections_2')).toBeAttached();

            await page.locator('#amendmentStatus').first().selectOption(STATUS_COMPLETED);
            await page.locator('#amendmentStatusString').first().fill('völlig erschöpft');
            await page.locator('#amendmentTitlePrefix').first().fill('Ä1neu');
            await page.locator('#amendmentDateCreation').first().fill('01.01.2015 01:02');
            await page.locator('#amendmentDateResolution').first().fill('02.03.2015 04:05');
            await page.locator('#amendmentNoteInternal').first().fill('Test 123');
            await appendCkEditorContent(page, 'sections_2_wysiwyg', '<p>Test 123</p>');
            await setCkEditorContent(page, 'amendmentReason_wysiwyg', '<p>Another Reason</p>');
            await amendment.saveForm();
        });

        await test.step('verify the changes are visible', async () => {
            await page.locator('.sidebarActions .view').click();
            await expect(page.locator('body')).toContainText(/Ä1neu zu A2/i);
            await expect(page.locator('body')).toContainText('Erledigt (völlig erschöpft)');
            await expect(page.locator('p.inserted').first()).toContainText('Test 123');
            await expect(page.locator('body')).toContainText('Another Reason');
            await expect(page.locator('body')).toContainText('02.03.2015');
            await expect(page.locator('body')).toContainText('01.01.2015');
        });

        await test.step('see the changes in the motion list', async () => {
            await gotoMotionList(page);
            await expect(page.locator('.amendment1').first()).toContainText('Ä1neu');
            await expect(page.locator('.amendment1').first()).toContainText(
                'Erledigt (völlig erschöpft)',
            );
        });
    });
});
