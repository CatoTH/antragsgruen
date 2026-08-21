import { test, expect } from '../../fixtures';

import { MotionPage } from '../../pages/MotionPage';
import { AmendmentPage } from '../../pages/AmendmentPage';
import { loginAsStdAdmin } from '../../utils/auth';
import {
    appendCkEditorContent,
    dispatchClick,
    focusCkEditor,
    replaceInCkEditor,
    setCkEditorContent,
} from '../../utils/dom';

test.describe('Admin: MotionEdit', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('edit a motion text, see no conflicts, see a conflict', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await page
            .locator('.motion2 .edit, .motion2 [href*="edit"]')
            .first()
            .click();

        await expect(page.locator('#sections_1')).not.toBeAttached();
        await expect(page.locator('#sections_2')).not.toBeVisible();
        await expect(page.locator('.saveholder .checkAmendmentCollisions')).not.toBeVisible();
        await expect(page.locator('.saveholder .save')).toBeVisible();

        await page.locator('#motionTextEditCaller button').click();
        await expect(page.locator('#sections_2')).toBeAttached();
        await expect(page.locator('.saveholder .checkAmendmentCollisions')).toBeVisible();
        await expect(page.locator('.saveholder .save')).not.toBeVisible();

        await page.locator('#motionStatus').selectOption('9');
        await page.locator('#motionStatusString').fill('völlig erschöpft');

        await page.locator('#motionTitle').fill('Neuer Titel');
        await page.locator('#motionTitlePrefix').fill('A2neu');
        await page.locator('#motionDateCreation').fill('01.01.2015 01:02');
        await page.locator('#motionDateSubmission').fill('01.02.2015 01:02');
        await page.locator('#motionDateResolution').fill('02.03.2015 04:05');
        await page.locator('#motionNoteInternal').fill('Test 123');
        await appendCkEditorContent(page, 'sections_2_wysiwyg', '<p>Test 123</p>');

        await expect(page.locator('.amendmentCollisionsHolder .alert-success')).not.toBeVisible();
        await dispatchClick(page, '.saveholder .checkAmendmentCollisions');
        await expect(page.locator('.amendmentCollisionsHolder .alert-success')).toBeVisible();
        await expect(page.locator('.saveholder .checkAmendmentCollisions')).not.toBeVisible();
        await expect(page.locator('.saveholder .save')).toBeVisible();

        await focusCkEditor(page, 'sections_2_wysiwyg');
        await expect(page.locator('.saveholder .checkAmendmentCollisions')).toBeVisible();
        await expect(page.locator('.saveholder .save')).not.toBeVisible();

        await replaceInCkEditor(
            page,
            'sections_2_wysiwyg',
            'Wui helfgod Wiesn',
            'Wui helfgod Wiesn1',
        );
        await dispatchClick(page, '.saveholder .checkAmendmentCollisions');
        await expect(page.locator('.amendmentCollisionsHolder .alert-success')).not.toBeVisible();
        await expect(page.locator('.amendmentCollisionsHolder .alert-danger')).toBeVisible();
        await expect(page.locator('#amendmentOverride_274_2_7')).toBeVisible();

        await replaceInCkEditor(
            page,
            'amendmentOverride_274_2_7',
            'Bla ,',
            'Bla,',
        );

        await page.locator('#motionUpdateForm [name="save"]').click();
    });

    test('verify the changes are visible', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await page
            .locator('.motion2 .edit, .motion2 [href*="edit"]')
            .first()
            .click();
        await page.locator('.sidebarActions .view').click();
        await expect(page.locator('body')).toContainText('A2NEU: NEUER TITEL');
        await expect(page.locator('body')).toContainText('Test 123');
        await expect(page.locator('body')).toContainText('02.03.2015');
        await expect(page.locator('body')).toContainText('01.02.2015');
        await expect(page.locator('body')).not.toContainText('01.01.2015');
        await expect(page.locator('body')).toContainText('Erledigt (völlig erschöpft)');
        await expect(page.locator('body')).toContainText('Wui helfgod Wiesn1');
    });

    test('verify the changes are visible in the amendments', async ({ page }) => {
        await loginAsStdAdmin(page);
        await new AmendmentPage(page).open({
            motionSlug: '321-o-zapft-is',
            amendmentId: 274,
        });
        await expect(page.locator('body')).toContainText('Wui helfgod Wiesn1Bla');

        await new AmendmentPage(page).open({
            motionSlug: '321-o-zapft-is',
            amendmentId: 3,
        });
        await expect(page.locator('body')).not.toContainText('Wui helfgod Wiesn');
    });

    test('see the changes in the motion list', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await expect(page.locator('.motion2')).toContainText('A2neu');
        await expect(page.locator('.motion2')).toContainText('Neuer Titel');
        await expect(page.locator('.motion2')).toContainText('Erledigt (völlig erschöpft)');
    });
});