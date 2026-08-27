import { test, expect } from '../../fixtures';
import {
    appendCkEditorContent,
    dispatchClick,
    focusCkEditor,
    replaceInCkEditor,
} from '../../utils/dom';
import {
    gotoAmendment,
    gotoMotionList,
    loginAndGotoMotionList,
} from '../../utils/navigation';

// app\models\db\IMotion::STATUS_COMPLETED
const STATUS_COMPLETED = '9';

test.describe('Admin: MotionEdit', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('edit a motion', async ({ page }) => {
        const motion = await test.step('edit a motion', async () => {
            const list = await loginAndGotoMotionList(page);
            const motion = await list.gotoMotionEdit(2);

            await expect(page.locator('#sections_1')).not.toBeAttached();
            await expect(page.locator('#sections_2').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.saveholder .checkAmendmentCollisions').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.saveholder .save').first()).toBeVisible();

            await page.locator('#motionTextEditCaller button').click();
            await expect(page.locator('#sections_2')).toBeAttached();
            await expect(page.locator('.saveholder .checkAmendmentCollisions').first()).toBeVisible();
            await expect(page.locator('.saveholder .save').filter({ visible: true })).toHaveCount(0);

            await page.locator('#motionStatus').first().selectOption(STATUS_COMPLETED);
            await page.locator('#motionStatusString').first().fill('völlig erschöpft');

            await page.locator('#motionTitle').first().fill('Neuer Titel');
            await page.locator('#motionTitlePrefix').first().fill('A2neu');
            await page.locator('#motionDateCreation').first().fill('01.01.2015 01:02');
            await page.locator('#motionDateSubmission').first().fill('01.02.2015 01:02');
            await page.locator('#motionDateResolution').first().fill('02.03.2015 04:05');
            await page.locator('#motionNoteInternal').first().fill('Test 123');
            await appendCkEditorContent(page, 'sections_2_wysiwyg', '<p>Test 123</p>');

            return motion;
        });

        await test.step('see no conflicts', async () => {
            await expect(
                page.locator('.amendmentCollisionsHolder .alert-success'),
            ).not.toBeVisible();
            await dispatchClick(page, '.saveholder .checkAmendmentCollisions');
            await expect(page.locator('.amendmentCollisionsHolder .alert-success').first()).toBeVisible();
            await expect(page.locator('.saveholder .checkAmendmentCollisions').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.saveholder .save').first()).toBeVisible();

            await focusCkEditor(page, 'sections_2_wysiwyg');
            await expect(page.locator('.saveholder .checkAmendmentCollisions').first()).toBeVisible();
            await expect(page.locator('.saveholder .save').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('see a conflict', async () => {
            await replaceInCkEditor(
                page,
                'sections_2_wysiwyg',
                'Wui helfgod Wiesn',
                'Wui helfgod Wiesn1',
            );
            await dispatchClick(page, '.saveholder .checkAmendmentCollisions');
            await expect(
                page.locator('.amendmentCollisionsHolder .alert-success'),
            ).not.toBeVisible();
            await expect(page.locator('.amendmentCollisionsHolder .alert-danger').first()).toBeVisible();
            await expect(page.locator('#amendmentOverride_274_2_7').first()).toBeVisible();

            await replaceInCkEditor(page, 'amendmentOverride_274_2_7', 'Bla ,', 'Bla,');

            // @TODO Change tags
            await motion.saveForm();
        });

        await test.step('verify the changes are visible', async () => {
            await page.locator('.sidebarActions .view').click();
            await expect(page.locator('body')).toContainText(/A2neu: Neuer Titel/i);
            await expect(page.locator('body')).toContainText('Test 123');
            await expect(page.locator('body')).toContainText('02.03.2015');
            await expect(page.locator('body')).toContainText('01.02.2015');
            await expect(page.locator('body')).not.toContainText('01.01.2015', { useInnerText: true });
            await expect(page.locator('body')).toContainText('Erledigt (völlig erschöpft)');
            await expect(page.locator('body')).toContainText('Wui helfgod Wiesn1');
        });

        await test.step('verify the changes are visible in the amendments', async () => {
            await gotoAmendment(page, true, '2', 274);
            await expect(page.locator('body')).toContainText('Wui helfgod Wiesn1Bla');

            await gotoAmendment(page, true, '2', 3);
            // Codeception's dontSee() only considers *visible* text; the amendment view keeps the
            // unchanged paragraphs in the DOM but collapsed, so this has to use innerText.
            await expect(page.locator('body')).not.toContainText('Wui helfgod Wiesn', {
                useInnerText: true,
            });
        });

        await test.step('see the changes in the motion list', async () => {
            await gotoMotionList(page);
            await expect(page.locator('.motion2').first()).toContainText('A2neu');
            await expect(page.locator('.motion2').first()).toContainText('Neuer Titel');
            await expect(page.locator('.motion2').first()).toContainText(
                'Erledigt (völlig erschöpft)',
            );
        });
    });
});
